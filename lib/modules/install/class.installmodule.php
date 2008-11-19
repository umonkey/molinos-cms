<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class InstallModule implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    try {
      if (false === ($output = mcms::dispatch_rpc(__CLASS__, $ctx)))
        $output = self::onGet($ctx);
    } catch (Exception $e) {
      mcms::fatal($e);
      $output = mcms::render(__CLASS__, array(
        'mode' => 'error',
        'message' => $e->getMessage(),
        ));
    }

    if (is_array($output))
      $output = mcms::render(__CLASS__, $output);

    if (false === $output)
      mcms::fatal(t('Не удалось обработать запрос.'));

    mcms::fixurls($output, true);
  }

  /**
   * Обработка отсутствия БД.
   */
  public static function rpc_db(Context $ctx)
  {
    if ('sqlite:conf/default.db' == mcms::config('db.default')) {
      // Позволяем отключить драйвер в конфиге.
      if (in_array('sqlite', PDO_Singleton::listDrivers())) {
        if (file_exists($dist = 'conf' . DIRECTORY_SEPARATOR . 'default.db.dist')) {
          if (copy($dist, substr($dist, 0, -5))) {
            $ctx->gonext();
          }
        }
      }
    }

    return false;
  }

  public static function rpc_install(Context $ctx)
  {
    $node = new InstallerNode();
    $node->formProcess($ctx->post);

    ExchangeModule::import(mcms::mkpath(array('lib', 'modules', 'exchange', 'profiles', $node->template)), true);

    $ctx->redirect('?q=admin');
  }

  protected static function onGet(Context $ctx)
  {
    if (!$ctx->method('get'))
      mcms::fatal('oops.');

    if (self::checkInstalled()) {
      mcms::fatal(t('Система уже установлена, см. <a href="@url1">сайт</a> или <a href="@url2">админку</a>.', array(
        '@url1' => '.',
        '@url2' => '?q=admin',
        )));
    }

    mcms::check();

    $node = new InstallerNode();

    return array(
      'mode' => 'form',
      'form' => $node->formGet()->getHTML($node),
      );
  }

  private static function checkInstalled()
  {
    try {
      if (Node::count(array())) {
        return true;
      }
    } catch (Exception $e) { }

    return false;
  }

  public static function onPost(Context $ctx)
  {
    $pdo = null;

    if (empty($_POST['config']['backtracerecipient']))
      throw new RuntimeException('Не указан почтовый адрес администратора.');

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
      throw new RuntimeException('Не удалось найти профиль пользователя.');
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
}

/**
 * Фиктивная нода, используется для построения формы инсталлера.
 */
class InstallerNode extends Node
{
  public function __construct()
  {
    return parent::__construct(array());
  }

  public function schema()
  {
    return new Schema(array(
      'debuggers' => array(
        'group' => t('Управление'),
        'type' => 'ListControl',
        'label' => t('Адреса разработчиков сайта'),
        'default' => '127.0.0.1, ' . $_SERVER['REMOTE_ADDR'],
        'required' => true,
        'description' => t('Пользователям с этих адресов будут доступны отладочные функции.'),
        ),

      'filestorage' => array(
        'group' => t('Файлы'),
        'type' => 'TextLineControl',
        'label' => t('Папка для файлов, загружаемых через браузер'),
        'default' => 'storage',
        'required' => true,
        ),
      'ftpfolder' => array(
        'group' => t('Файлы'),
        'type' => 'TextLineControl',
        'label' => t('Папка для файлов, загружаемых по FTP'),
        'description' => t('Доступ к этой папке по протоколу FTP нужно настраивать отдельно, CMS сделать это не в состоянии.'),
        'default' => 'storage' . DIRECTORY_SEPARATOR .'ftp',
        ),
      'tmpdir' => array(
        'group' => t('Файлы'),
        'type' => 'TextLineControl',
        'label' => t('Папка для временных файлов'),
        'description' => t('Желательно сделать так, чтоб эта папка была недоступна извне'),
        'default' => 'tmp',
        'required' => true,
        ),

      'mail_server' => array(
        'group' => t('Почта'),
        'type' => 'TextLineControl',
        'label' => t('Адрес почтового сервера'),
        'default' => 'localhost',
        'required' => true,
        ),
      'mail_from' => array(
        'group' => t('Почта'),
        'type' => 'EmailControl',
        'label' => t('Отправитель сообщений'),
        'default' => 'no-reply@' . $_SERVER['HTTP_HOST'],
        'required' => true,
        ),
      'backtracerecipients' => array(
        'group' => t('Почта'),
        'type' => 'EmailControl',
        'label' => t('Получатели сообщений об ошибках'),
        'description' => t('Сообщения об ошибках в коде CMS отправляются на эти адреса в момент обнаружения.'),
        'default' => 'cms-bugs@molinos.ru',
        ),

      'db_type' => array(
        'group' => t('База данных'),
        'type' => 'EnumControl',
        'label' => t('Тип базы данных'),
        'required' => true,
        'options' => $this->listDrivers(),
        ),
      'db_name' => array(
        'group' => t('База данных'),
        'type' => 'TextLineControl',
        'label' => t('Имя базы данных'),
        'description' => t('Перед инсталляцией база данных будет очищена от существующих данных, сделайте резервную копию!'),
        ),
      'db_host' => array(
        'group' => t('База данных'),
        'type' => 'TextLineControl',
        'label' => t('Адрес сервера'),
        'default' => 'localhost',
        ),
      'db_username' => array(
        'group' => t('База данных'),
        'type' => 'TextLineControl',
        'label' => t('Имя пользователя'),
        ),
      'db_password' => array(
        'group' => t('База данных'),
        'type' => 'PasswordControl',
        'label' => t('Пароль этого пользователя'),
        ),

      'template' => array(
        'group' => t('Заготовка'),
        'type' => 'EnumControl',
        'label' => t('Базовое наполнение'),
        'required' => true,
        'options' => $this->listProfiles(),
        're' => '/^[a-z0-9]+\.xml$/',
        ),
      ));
  }

  public function checkPermission($mode)
  {
    switch ($mode) {
    case 'c':
    case 'u':
      return true;
    default:
      return false;
    }
  }

  public function formGet()
  {
    $form = parent::formGet();
    $form->addClass('tabbed');
    return $form;
  }

  public function formProcess(array $data)
  {
    parent::formProcess($data);

    $this->writeConfig();

    return $this;
  }

  public function getFormTitle()
  {
    return t('Установка Molinos CMS');
  }

  public function getFormSubmitText()
  {
    return t('Начать установку');
  }

  public function getFormAction()
  {
    $next = empty($_GET['destination'])
      ? '?q=admin'
      : $_GET['destination'];

    return '?q=install.rpc&action=install&destination=' . urlencode($next) . '&debug=errors';
  }

  private function listDrivers()
  {
    $options = array();

    if (class_exists('PDO', false)) {
      foreach (PDO_Singleton::listDrivers() as $el) {
        $title = null;

        switch ($el) {
        case 'sqlite':
          $title = 'SQLite';
          break;
        case 'mysql':
          $title = 'MySQL';
          break;
        }

        if (null !== $title)
          $options[$el] = $title;
      }

      if (empty($options)) {
        throw new Exception(t('Нет доступных драйверов PDO; рекоммендуем установить <a href=\'@url\'>PDO_SQLite</a> (или <a href=\'@url2\'>PDO_MySQL</a>, но SQLite проще и быстрее).', array(
          '@url' => 'http://docs.php.net/manual/en/ref.pdo-sqlite.php',
          '@url2' => 'http://docs.php.net/manual/en/ref.pdo-mysql.php',
          )));
      }
    } else {
      throw new Exception(t('Для использования Molinos.CMS нужно установить расширение <a href=\'@url\'>PDO</a>.', array('@url' => 'http://docs.php.net/manual/en/book.pdo.php')));
    }

    asort($options);

    return $options;
  }

  private function listProfiles()
  {
    $options = array();

    foreach (ExchangeModule::getProfileList() as $pr)
      $options[$pr['filename']] = $pr['name'];

    return $options;
  }

  private function writeConfig()
  {
    $data = array();

    switch ($this->db_type) {
    case 'sqlite':
      $data['db.default'] = 'sqlite:' . $this->db_name;
      break;
    default:
      $data['db.default'] = $this->db_type . '://';
      if ($this->db_username) {
        $data['db.default'] .= $this->db_username;
        if ($this->db_password)
          $data['db.default'] .= ':' . $this->db_password;
        $data['db.default'] .= '@';
      }
      $data['db.default'] .= $this->db_host;
      if ($this->db_name)
        $data['db.default'] .= '/' . $this->db_name;
    }

    foreach (array('mail_server', 'mail_from', 'debuggers') as $k)
      $data[str_replace('_', '.', $k)] = $this->$k;

    $config = Config::getInstance();

    foreach ($data as $k => $v)
      $config->$k = $v;

    $config->write();

    mcms::flush();
    mcms::flush(mcms::FLUSH_NOW);
  }
}
