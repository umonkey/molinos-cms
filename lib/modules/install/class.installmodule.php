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

    return $output;
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
          $target = substr($dist, 0, -5);

          if (!file_exists($target))
            if (!copy($dist, $target))
              throw new RuntimeException(t('Не удалось проинсталлировать новую базу данных.'));
        }
      }
    }
  }

  public static function rpc_install(Context $ctx)
  {
    $node = new InstallerNode();
    $node->formProcess($ctx->post);
    $node->writeConfig($ctx);

    ExchangeModule::import(mcms::mkpath(array('lib', 'modules', 'exchange', 'profiles', $node->template)), true);

    $s = new Structure();
    $s->rebuild();

    return new Redirect('?q=admin');
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

  public function getFormFields()
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
        throw new Exception(t('Нет доступных драйверов PDO; рекоммендуем установить <a href=\'@url1\'>PDO_SQLite</a> (или <a href=\'@url2\'>PDO_MySQL</a>, но SQLite проще и быстрее).', array(
          '@url1' => 'http://docs.php.net/manual/en/ref.pdo-sqlite.php',
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

  public function writeConfig(Context $ctx)
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

    $ctx->db = $data['db.default'];

    mcms::flush();
    mcms::flush(mcms::FLUSH_NOW);
  }
}
