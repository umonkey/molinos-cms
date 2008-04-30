<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

if (!version_compare(PHP_VERSION, "5.2.0", ">")) {
  header('Content-Type: text/plain; charset=utf-8');
  die("Molinos.CMS requires PHP 5.2.0 or later.");
}

require(dirname(__FILE__) .'/lib/bootstrap.php');
require(dirname(__FILE__) .'/lib/modules/pdo/exception.mcmspdo.php');

ob_start();

$installer = new BebopInstaller();
$installer->run();

class BebopInstaller
{
  public function run()
  {
    switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
      $data = $this->onGet();
      break;
    case 'POST':
      $data = $this->onPost();
      break;
    default:
      header('Content-Type: text/plain; charset=utf-8');
      die('Sorry, what?');
    }

    $output = bebop_render_object("system", "installer", "admin", $data);

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Length: '. strlen($output));
    die($output);
  }

  protected function onGet()
  {
    if ($this->checkInstalled())
      bebop_redirect('/');

    $data = array();
    $plist = ExchangeModule::getProfileList();

    $form = new Form(array(
      'title' => t('Инсталляция Molinos CMS'),
      'description' => t("Вам нужно последовательно заполнить все поля во всех вкладках."),
      'class' => 'tabbed',
      ));

    $tab = new FieldSetControl(array(
      'name' => 'mysql',
      'label' => t('База данных'),
      ));

    if (!empty($_GET['msg']) and $_GET['msg'] == 'notable')
      $tab->addControl(new InfoControl(array(
        'text' => t('Вы были перенаправлены на страницу инсталляции, т.к. некоторые жизненно важные таблицы не были обнаружены в базе данных.  Скорее всего Molinos.CMS не установлена.'),
        )));

    $tab->addControl(new EnumControl(array(
      'value' => 'db[type]',
      'label' => t('Тип базы данных'),
      'required' => true,
      'options' => $this->listDrivers(),
      'id' => 'dbtype',
      'description' => t('Для разработки рекоммендуется использовать SQLite; перенести сайт на MySQL будет можно.'),
      'default' => 'sqlite',
      )));

    $tab->addControl(new TextLineControl(array(
      'value' => 'db[name]',
      'label' => t('Имя базы данных'),
      'description' => t("Перед инсталляцией база данных будет очищена от существующих данных, сделайте резервную копию!"),
      )));

    $tab->addControl(new TextLineControl(array(
      'value' => 'db[host]',
      'label' => t('MySQL сервер'),
      'wrapper_id' => 'db-server',
      )));

    $tab->addControl(new TextLineControl(array(
      'value' => 'db[user]',
      'label' => t('Пользователь MySQL'),
      'wrapper_id' => 'db-user',
      )));
    $tab->addControl(new PasswordControl(array(
      'value' => 'db[pass]',
      'label' => t('Пароль этого пользователя'),
      'wrapper_id' => 'db-password',
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'site',
      'label' => t('Сайт'),
      ));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config[basedomain]',
      'label' => t('Основной домен'),
      'description' => t("Административный интерфейс также будет работать в этом домене."),
      )));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config[backtracerecipient]',
      'label' => t('Получатели сообщений об ошибках'),
      'description' => t("Один или несколько почтовых адресов, на которые будет отправляться информация о фатальных ошибках.&nbsp; Если здесь ничего не указывать, ошибки никуда отправляться не будут."),
      )));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config[debuggers]',
      'label' => t('IP-адреса отладчиков'),
      'description' => t("Один или несколько IP адресов, пользователям которых будут доступны дополнительные отладочные функции.&nbsp; Обычто этим пользуются только разработчики сайта."),
      )));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config[mail_from]',
      'label' => t('Обратный почтовый адрес'),
      'description' => t('Этот адрес будет использован в качестве отправителя всех почтовых сообщений, рассылаемых сайтом.'),
      )));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config[mail_server]',
      'label' => t('Почтовый сервер'),
      'description' => t('Через этот SMTP сервер будет отправляться вся почта.'),
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'profiles',
      'label' => t('Профили')
      ));

    $options = array();

    for ($i = 0; $i < count($plist); $i++) {
      $pr = $plist[$i];
      $options[$pr['filename']] = $pr['name'];
    }

    $tab->addControl(new EnumControl(array(
      'value' => 'profile',
      'label' => t('Выберите профиль'),
      'required' => true,
      'options' => $options,
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
      'config[mail_from]' => "Molinos.CMS <no-reply@{$_SERVER['HTTP_HOST']}>",
      'config[mail_server]' => 'localhost',
      );

    return array(
      'form' => $form->getHTML($data),
      );
  }

  protected function onPost()
  {
    $pdo = null;

    // Сбрасываем авторизацию.
    //User::authorize();

    $data = array(
      'title' => 'Инсталляция Molinos CMS',
      'form' => '<p>Установка системы завершена.</p>',
      );

    Installer::writeConfig($_POST);

    // Теперь можно попробовать запустить транзакцию.
    try {
      $pdo = mcms::db();
      $pdo->beginTransaction();
    } catch (Exception $e) {
      $pdo = null;
    }

    // создадим таблицы
    Installer::CreateTables();

    // Всё хорошо, можно сохранять изменниея.
    if (null !== $pdo)
      $pdo->commit();

    // Импортируем профиль.
    if (!empty($_POST['profile']))
      ExchangeModule::import("lib/modules/exchange/profiles/{$_POST['profile']}", true);

    // Логинимся в качестве рута.
    User::authorize('root', null, true);

    if (null === mcms::user() or null === mcms::user()->id)
      throw new RuntimeException(t('Ошибка инсталляции: не удалось получить идентификатор пользователя.'));

    mcms::flush(mcms::FLUSH_NOW);

    $data['form'] .= '<p>'. t("Вы были автоматически идентифицированы как пользователь &laquo;%username&raquo;.&nbsp; Пароль для этого пользователя был сгенерирован случайным образом, поэтому сейчас лучше всего <a href='@editlink'>изменить пароль</a> на какой-нибудь, который Вы знаете, а потом уже продолжить <a href='@adminlink'>пользоваться системой</a>.", $args = array(
      '%username' => 'root',
      '@editlink' => '/admin/?mode=edit&cgroup=access&id='. mcms::user()->id .'&destination=/admin/',
      '@adminlink' => '/admin/'
      )) .'</p>';

    return $data;
  }

  private function getConfigPath($domain)
  {
    $result = array();

    for ($parts = explode('.', $domain); !empty($parts); $result[] = array_pop($parts));

    return 'conf/'. join('.', $result) .'.ini';
  }

  private function checkInstalled()
  {
    try {
      if (Node::count(array()))
        return true;
    } catch (Exception $e) {
      bebop_debug($e);
    }

    return false;
  }

  private function listDrivers()
  {
    $options = array();

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
      }

      $options[$el] = $title;
    }

    asort($options);

    return $options;
  }
};
