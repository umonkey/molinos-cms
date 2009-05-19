<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class InstallModule
{
  public static function rpc_get_default(Context $ctx)
  {
    if ($ctx->config->isOk())
      $ctx->redirect('admin');

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

    // Выносим секцию main в самое начало.
    $config['main'] = array();

    if ($config->isok())
      throw new ForbiddenException(t('Инсталляция невозможна: конфигурационный файл уже есть.'));

    $config->set('modules/db', self::getDSN($data['dbtype'], $data['db'][$data['dbtype']]));

    foreach (array('modules/mail/server', 'modules/mail/from', 'main/debug/errors') as $key)
      if (!empty($data[$key]))
        $config->set($key, $data[$key]);

    $config->set('modules/files/storage', 'files');
    $config->set('modules/files/ftp', 'ftp');
    $config->set('main/tmpdir', 'tmp');
    $config->set('main/debug/allow', array('127.0.0.1', $_SERVER['REMOTE_ADDR']));

    // Создаём маршрут для главной страницы.
    $config['routes']['localhost/'] = array(
      'title' => 'Molinos CMS',
      'theme' => 'example',
      'call' => 'BaseRoute::serve',
      );

    // Проверим соединение с БД.
    $pdo = PDO_Singleton::connect($config->get('modules/db'));

    // Подключились, можно сохранять конфиг.
    $config->save();

    $ctx->redirect('admin/system/reload?destination=admin/system/settings');
  }

  private static function getDSN($type, array $settings)
  {
    $result = array(
      'type' => $type,
      );

    switch ($type) {
    case 'sqlite':
      $keys = array('name');
      break;
    case 'mysql':
      $keys = array('user', 'password', 'host', 'name');
      break;
    default:
      throw new RuntimeException(t('БД типа "%type" не поддерживается.', array(
        '%type' => $type,
        )));
    }

    foreach ($keys as $key)
      $result[$key] = $settings[$key];

    return $result;
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
