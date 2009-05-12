<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class InstallModule
{
  public static function rpc_get_default(Context $ctx)
  {
    $xml = self::listDriversXML();
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
    if ($config->isok())
      throw new ForbiddenException(t('Инсталляция невозможна: конфигурационный файл уже есть.'));

    $config->db_dsn = self::getDSN($data['dbtype'], $data['db'][$data['dbtype']]);

    foreach (array('mail_server', 'mail_from', 'debug_errors') as $key) {
      if (!empty($data[$key])) {
        $config->$key = $data[$key];
      }
    }

    $config->base_files = 'files';
    $config->base_ftp = 'ftp';
    $config->base_tmpdir = 'tmp';

    // Формируем список отладчиков.
    $debuggers = '127.0.0.1,' . $_SERVER['REMOTE_ADDR'];
    $config->debug_allow = substr($debuggers, 0, strrpos($debuggers, '.')) . '.*';

    // Проверим соединение с БД.
    $pdo = PDO_Singleton::connect($config->db_dsn);

    $config->write();
    $ctx->redirect('admin/system/reload?destination=%3Fq%3Dadmin%2Fsystem');

    /*
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
