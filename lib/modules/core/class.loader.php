<?php

define('MCMS_RELEASE', '9.05');
define('MCMS_VERSION', '9.05B4');

$dirName = dirname(__FILE__);
foreach (array('os', 'ini', 'registry', 'cache', 'config', 'router', 'context', 'logger') as $className)
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
    chdir(MCMS_ROOT);

    if (!defined('MCMS_HOST_NAME'))
      define('MCMS_HOST_NAME', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');

    define('MCMS_SITE_FOLDER', self::find_site_folder(MCMS_HOST_NAME));
    define('MCMS_WEB_FOLDER', isset($_SERVER['SCRIPT_NAME']) ? rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') : '');

    define('MCMS_START_TIME', microtime(true));

    if (!defined('MCMS_REQUEST_URI'))
      define('MCMS_REQUEST_URI', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');

    if (!defined('MCMS_TEMP_FOLDER'))
      define('MCMS_TEMP_FOLDER', Context::last()->config->get('main/tmpdir', MCMS_SITE_FOLDER . DIRECTORY_SEPARATOR . 'tmp'));

    if (!defined('MCMS_CONSOLE'))
      define('MCMS_CONSOLE', empty($_SERVER['REMOTE_ADDR']));

    mb_internal_encoding('utf-8');
  }

  private static function find_site_folder($hostName)
  {
    $options = array();
    for ($parts = array_reverse(explode('.', $hostName)); !empty($parts); array_pop($parts))
      $options[] = 'sites' . DIRECTORY_SEPARATOR . join('.', $parts);
    $options[] = $default = 'sites' . DIRECTORY_SEPARATOR . 'default';

    $prefix = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

    foreach ($options as $path)
      if (is_dir($prefix . $path))
        return $path;

    header('Content-Type: text/plain; charset=utf-8');
    die('Домен ' . $hostName . ' не обслуживается.');
  }

  public static function run()
  {
    try {
      self::setup();

      $ctx = Context::last();

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
      Logger::trace($e);
      header('HTTP/1.1 500 FUBAR');
      header('Content-Type: text/plain; charset=utf-8');
      die(sprintf('%s: %s.', get_class($e), rtrim($e->getMessage(), '.')));
    }
  }
}
