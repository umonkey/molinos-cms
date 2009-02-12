<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class InstallModule implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    return mcms::dispatch_rpc(__CLASS__, $ctx, 'install');
  }

  public static function rpc_get_install(Context $ctx)
  {
    $xml = self::listDriversXML();
    $xml .= self::getForm()->getXML(Control::data());
    $xsl = os::path('lib', 'modules', 'install', 'template.xsl');
    return xslt::transform(html::em('installer', array(
      'base' => $ctx->url()->getBase($ctx),
      'dirname' => $ctx->config->getDirName(),
      ), $xml), $xsl);
  }

  public static function rpc_post_install(Context $ctx)
  {
    $data = $ctx->post;

    if (empty($data['dbtype']))
      throw new RuntimeException(t('Вы не выбрали тип БД.'));

    $config = $ctx->config;
    $config->db = self::getDSN($data['dbtype'], $data['db'][$data['dbtype']]);

    foreach (array('mail_server', 'mail_from', 'mail_errors', 'tmpdir', 'files', 'files_ftp', 'themes') as $key)
      if (!empty($data[$key]))
        $config->$key = $data[$key];

    // Проверим соединение с БД.
    $pdo = PDO_Singleton::connect($config->db);

    $config->write();
    $ctx->redirect('?q=admin');


    /*
    $node = new InstallerNode();
    $node->formProcess($ctx->post);
    $node->writeConfig($ctx);

    ExchangeModule::import(mcms::mkpath(array('lib', 'modules', 'exchange', 'profiles', $node->template)), true);

    $s = new Structure();
    $s->rebuild();

    */
  }

  private static function getDSN($type, array $settings)
  {
    switch ($type) {
    case 'sqlite':
      return 'sqlite:' . $settings['name'];
    case 'mysql':
      return 'mysql://' . $settings['user'] . ':' . $settings['pass'] . '@' . $settings['host'] . '/' . $settings['name'];
    default:
      throw new RuntimeException(t('БД типа "%type" не поддерживается.', array(
        '%type' => $type,
        )));
    }
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

  private static function getForm()
  {
    $schema = new Schema(array(
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
        'options' => self::listDrivers(),
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
      ));
    
    return $schema->getForm();
  }

  private static function listDrivers()
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

  private static function listDriversXML()
  {
    $output = '';

    foreach (self::listDrivers() as $k => $v)
      $output .= html::em('driver', array(
        'name' => $k,
        'title' => $v,
        ));

    return html::em('drivers', $output);
  }
}
