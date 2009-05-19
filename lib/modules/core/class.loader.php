<?php

define('MCMS_RELEASE', '9.05');
define('MCMS_VERSION', '9.05B1');

$dirName = dirname(__FILE__);
foreach (array('os', 'ini', 'registry', 'cache', 'config', 'router', 'context') as $className)
  if (!class_exists($className))
    require $dirName . DIRECTORY_SEPARATOR . 'class.' . $className . '.php';
unset($dirName);
unset($className);

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'functions.php';

class Loader
{
  public static function setup()
  {
    define('MCMS_ROOT', dirname(dirname(dirname(dirname(realpath(__FILE__))))));

    if (!defined('MCMS_HOST_NAME'))
      define('MCMS_HOST_NAME', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');

    define('MCMS_SITE_FOLDER', self::find_site_folder(MCMS_HOST_NAME));
    define('MCMS_WEB_FOLDER', isset($_SERVER['SCRIPT_NAME']) ? rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') : '');

    define('MCMS_START_TIME', microtime(true));

    if (!defined('MCMS_REQUEST_URI'))
      define('MCMS_REQUEST_URI', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
  }

  private static function find_site_folder($hostName)
  {
    $options = array();
    for ($parts = array_reverse(explode('.', $hostName)); !empty($parts); array_pop($parts))
      $options[] = 'sites' . DIRECTORY_SEPARATOR . join('.', $parts);
    $options[] = $default = 'sites' . DIRECTORY_SEPARATOR . 'default';

    foreach ($options as $path)
      if (is_dir($path))
        return $path;

    header('Content-Type: text/plain; charset=utf-8');
    die('Домен ' . $hostName . ' не обслуживается.');
  }

  public static function run()
  {
    self::setup();

    try {
      $ctx = new Context();

      if ('admin/install' != $ctx->query() and !$ctx->config->isOk())
        $ctx->redirect('admin/install');

      $router = new Router();
      $result = $router->poll($ctx)->dispatch($ctx);

      if ($result instanceof Response)
        $result->send();

      elseif (false === $result) {
        header('HTTP/1.1 404 Not Found');
        header('Content-Type: text/plain; charset=utf-8');
        die('Route Not Found.');
      } else {
        list($handler, $args) = $router->find($ctx);

        if (false === $handler)
          $method = '?unknown?';
        elseif (is_array($handler['call']))
          $method = implode('::', $handler['call']);
        else
          $method = $handler['call'];

        $message = t('<h1>Внутренняя ошибка</h1><p>Обработчик пути <tt>%path</tt> (<tt>%func</tt>) должен был вернуть объект класса <a href="@class">Response</a>, а вернул %type.</p><hr/><a href="@home">Molinos CMS v%version</a>', array(
          '%path' => $ctx->query(),
          '%type' => gettype($result),
          '%func' => $method,
          '@class' => 'http://code.google.com/p/molinos-cms/wiki/Response_Class',
          '%version' => MCMS_VERSION,
          '@home' => 'http://molinos-cms.googlecode.com/',
          ));

        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: text/html; charset=utf-8');
        die($message);
      }
    } catch (Exception $e) {
      mcms::fatal($e);
    }
  }
}
