<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class InstallModule extends RPCHandler
{
  /**
   * @mcms_message ru.molinos.cms.rpc.install
   */
  public static function on_rpc(Context $ctx)
  {
    return parent::hookRemoteCall($ctx, __CLASS__);
  }

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
    $config->db = self::getDSN($data['dbtype'], $data['db'][$data['dbtype']]);

    foreach (array('mail_server', 'mail_from', 'mail_errors', 'tmpdir', 'files', 'files_ftp', 'themes') as $key)
      if (!empty($data[$key]))
        $config->$key = $data[$key];

    // Проверим соединение с БД.
    $pdo = PDO_Singleton::connect($config->db);

    $config->write();
    $ctx->redirect('?q=admin');

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
