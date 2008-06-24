<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class InstallModule implements iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    if (self::checkInstalled())
      mcms::redirect("index.php"); //Защита от несанкционированного вызова инсталлятора
    $res = self::checkInstalled();

    //$mode = $ctx->post('exchmode');
    $result = '';

    try {
      switch ($_SERVER['REQUEST_METHOD']) {
      case 'GET':
        $data = self::onGet($ctx);
        break;
      case 'POST':
        $data = self::onPost($ctx);
        break;
      default:
        header('Content-Type: text/plain; charset=utf-8');
        die('Sorry, what?');
      }
    } catch (Exception $e) {
      $data = array(
        'title' => 'Ошибка',
        'form' => '<p>'. $e->getMessage() .'</p>',
        );
    }

    $output = bebop_render_object("system", "installer", "admin", $data);

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Length: '. strlen($output));
    die($output);
    //mcms::redirect("admin?module=exchange&preset=export&result=upgradeok");
  }

  public static function checkInstalled()
  {
    try {
      if (Node::count(array())) {
        return true;
      }
    } catch (Exception $e) {
      //mcms::debug($e);
    }

    return false;
  }

  public static function onGet(RequestContext $ctx)
  {
    self::checkEnvironment();

    if (null !== ($tmp = $ctx->get('result')))
      return self::getResult($tmp);

    $form = new Form(array(
      'title' => t('Инсталляция Molinos CMS'),
      'description' => t("Вам нужно последовательно заполнить все поля во всех вкладках."),
      ));

    $form->addClass('tabbed');

    $tab = new FieldSetControl(array(
      'name' => 'site',
      'label' => t('Сайт'),
      ));
    if (!empty($_GET['msg']) and $_GET['msg'] == 'notable')
      $tab->addControl(new InfoControl(array(
        'text' => t('Вы были перенаправлены на страницу инсталляции, т.к. некоторые жизненно важные таблицы не были обнаружены в базе данных.  Скорее всего Molinos.CMS не установлена.'),
        )));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config[backtracerecipient]',
      'label' => t('Ваш почтовый адрес'),
      'description' => t('На этот адрес будут приходить сообщения об ошибках, также он будет использоваться для входа в административный интерфейс.'),
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'mysql',
      'label' => t('База данных'),
      ));
    $tab->addControl(new EnumControl(array(
      'value' => 'db[type]',
      'label' => t('Тип базы данных'),
      'required' => true,
      'options' => self::listDrivers(),
      'id' => 'dbtype',
      'description' => t('Для разработки рекоммендуется использовать SQLite; перенести сайт на MySQL можно будет в несколько кликов.'),
      'default' => 'sqlite',
      )));
    $tab->addControl(new TextLineControl(array(
      'value' => 'db[name]',
      'label' => t('Имя базы данных'),
      'description' => t("Перед инсталляцией база данных будет очищена от существующих данных, сделайте резервную копию!"),
      )));
    $tab->addControl(new TextLineControl(array(
      'value' => 'db[host]',
      'label' => t('Адрес сервер'),
      'wrapper_id' => 'db-server',
      )));
    $tab->addControl(new TextLineControl(array(
      'value' => 'db[user]',
      'label' => t('Имя пользователя'),
      'wrapper_id' => 'db-user',
      )));
    $tab->addControl(new PasswordControl(array(
      'value' => 'db[pass]',
      'label' => t('Пароль этого пользователя'),
      'wrapper_id' => 'db-password',
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'profiles',
      'label' => t('Заготовка')
      ));

    $tab->addControl(new EnumControl(array(
      'value' => 'profile',
      'label' => t('Базовое наполнение'),
      'required' => true,
      'options' => self::getProfiles(),
      )));

    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'confirm',
      'label' => t('Подтверждение'),
      ));
    $tab->addControl(new BoolControl(array(
      'value' => 'confirm',
      'label' => t('Я подтверждаю свои намерения'),
      )));
    $tab->addControl(new SubmitControl(array(
      'text' => t('Поехали!'),
      )));
    $form->addControl($tab);

    $data = array(
      'db[host]' => 'localhost',
      'config[basedomain]' => $_SERVER['HTTP_HOST'],
      'config[backtracerecipient]' => 'cms-bugs@molinos.ru',
      'config[debuggers]' => $_SERVER['REMOTE_ADDR'] .', 127.0.0.1',
      'config[mail_from]' => "Molinos.CMS <no-reply@cms.molinos.ru>",
      'config[mail_server]' => 'localhost',
      );

    return  array(
      'form' => $form->getHTML($data),
      );
  }

  public static function onPost(RequestContext $ctx)
  {
    $pdo = null;

    if (empty($_POST['config']['backtracerecipient']))
      mcms::fatal('Не указан почтовый адрес администратора.');

    $data = array(
      'title' => 'Инсталляция Molinos CMS',
      'form' => '<p>Установка системы завершена.</p>',
      );

    self::writeConfig($_POST);

    mcms::db()->getInstance('default', true); // принудительное пересоздание instance

    // Теперь можно попробовать запустить транзакцию.
    try {
      $pdo = mcms::db();
      $pdo->beginTransaction();
    } catch (Exception $e) {
      $pdo = null;
    }

    // создадим таблицы
    //Installer::CreateTables();

    // Всё хорошо, можно сохранять изменниея.
    if (null !== $pdo)
      $pdo->commit();

    // Импортируем профиль.
    if (!empty($_POST['profile']))
      ExchangeModule::import("lib/modules/exchange/profiles/{$_POST['profile']}", true);

    // Правим профиль пользователя.
    if (!count($nodes = Node::find(array('class' => 'user', 'name' => 'cms-bugs@molinos.ru')))) {
      mcms::fatal('Не удалось найти профиль пользователя.');
    } else {
      $node = array_shift($nodes);
      $node->name = $_POST['config']['backtracerecipient'];
      $node->password = null;
      $node->save();
    }

    // Логинимся в качестве администратора.
    User::authorize($node->name, null, true);

    if (null === mcms::user() or null === mcms::user()->id)
      throw new RuntimeException(t('Ошибка инсталляции: не удалось получить идентификатор пользователя.'));

    mcms::flush(mcms::FLUSH_NOW);

    $data['form'] .= '<p>'. t("Вы были автоматически идентифицированы как пользователь &laquo;%username&raquo;.&nbsp; Пароль для этого пользователя был сгенерирован случайным образом, поэтому сейчас лучше всего <a href='@editlink'>изменить пароль</a> на какой-нибудь, который Вы знаете, а потом уже продолжить <a href='@adminlink'>пользоваться системой</a>.", $args = array(
      '%username' => 'root',
      '@editlink' => 'admin?mode=edit&cgroup=access&id='. mcms::user()->id .'&destination=admin',
      '@adminlink' => 'admin'
      )) .'</p>';

    return $data;
  }

  private static function getResult($mode)
  {
    $titles = array(
      'upgradeok' => t('Перенос прошёл успешно'),
      'importok' => t('Импорт прошёл успешно'),
      'exportok' => t('Экспорт прошёл успешно'),
      );

    $messages = array(
      'upgradeok' => t('База данных успешно перенесена в MySQL.  Вы можете <a href=\'@continue\'>продолжить пользоваться системой</a> в обычном режиме.  Чтобы переключиться обратно на SQLite, отредактируйте конфигурационный файл Molinos.CMS (обычно это conf/default.ini).', array('@continue' => 'admin')),
      'importok' => t('Восстановление бэкапа прошло успешно.'),
      'exportok' => null,
      );

    return '<h2>'. $titles[$mode] .'</h2><p>'. $messages[$mode] .'</p>';
  }

  private static function listDrivers()
  {
    $options = array();

    if (class_exists('PDO', false)) {
      foreach (PDO::getAvailableDrivers() as $el) {
        switch ($title = $el) {
        case 'sqlite':
          $title = 'SQLite';
          break;
        case 'sqlite2':
          $title = 'SQLite 2 (не рекоммендуется)';
          break;
        case 'mysql':
          $title = 'MySQL';
          break;
        case 'dblib':
          $title = 'DBLib (MSSQL, Sybase)';
          break;
        case 'odbc':
          $title = 'ODBC';
          break;
        }

        $options[$el] = $title;
      }

      if (empty($options))
        throw new Exception(t('Нет доступных драйверов PDO; рекоммендуем установить <a href=\'@url\'>PDO_SQLite</a>.', array('@url' => 'http://docs.php.net/manual/en/ref.pdo-sqlite.php')));
    } else {
      throw new Exception(t('Для использования Molinos.CMS нужно установить расширение <a href=\'@url\'>PDO</a>.', array('@url' => 'http://docs.php.net/manual/en/book.pdo.php')));
    }

    asort($options);

    return $options;
  }

  private static function getProfiles()
  {
    $options = array();

    foreach (ExchangeModule::getProfileList() as $pr)
      $options[$pr['filename']] = $pr['name'];

    return $options;
  }

  private static function checkEnvironment()
  {
    $errors = array();

    if (file_exists('conf/default.ini') and !is_writable('conf/default.ini'))
      $errors[] = t('Конфигурационный файл (conf/default.ini) закрыт для записи, инсталляция невозможна.');

    elseif (!file_exists('conf/default.ini') and !is_writable('conf'))
      $errors[] = t('Каталог с конфигурационными файлами (conf) закрыт для записи, инсталляция невозможна.');

    if (!empty($errors))
      throw new RuntimeException(join(';', $errors));
  }

  public static function writeConfig(array $data, $olddsn = null)
  {
    if (!empty($data['db']['pass']) and $data['db']['pass'][0] != $data['db']['pass'][1])
      throw new InvalidArgumentException(t('Пароль для подключения к БД введён некорректно.'));
    else
      $data['db']['pass'] = $data['db']['pass'][0];

    if (empty($data['confirm']))
      throw new InvalidArgumentException("Вы не подтвердили свои намерения.");

    $config = BebopConfig::getInstance();

    if ($olddsn)
      $config->set('default_backup', $olddsn, 'db');

    switch ($data['db']['type']) {
    case 'sqlite':
      if (empty($data['db']['name']))
        $data['db']['name'] = 'default.db';
      elseif (substr($data['db']['name'], -3) != '.db')
        $data['db']['name'] .= '.db';
      $config->set('default', 'sqlite:conf/'. $data['db']['name'], 'db');
      break;

    case 'mysql':
      $config->set('default', sprintf('mysql://%s:%s@%s/%s', $data['db']['user'], $data['db']['pass'], $data['db']['host'], $data['db']['name']), 'db');
      break;
    }

    foreach (self::getDefaults() as $k => $v) {
      $value = array_key_exists($k, $data['config']) ? $data['config'][$k] : $v;
      $config->set($k, $value);
    }

    $config->write();
  }

  private static function getDefaults()
  {
    return array(
      'mail_from' => 'no-reply@cms.molinos.ru',
      'mail_server' => 'localhost',
      'backtracerecipients' => 'cms-bugs@molinos.ru',
      'debuggers' => '127.0.0.1',
      'filestorage' => 'storage',
      'tmpdir' => 'tmp',
      'file_cache_ttl' => 3600,
      );
  }
}
