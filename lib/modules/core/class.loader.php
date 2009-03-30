<?php

define('MCMS_RELEASE', '9.03');
define('MCMS_VERSION', '9.03');

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class.os.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class.ini.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class.registry.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class.config.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'class.context.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'functions.php';

class Loader
{
  private static function setup()
  {
    define('MCMS_ROOT', dirname(dirname(dirname(dirname(realpath(__FILE__))))));

    if (!defined('MCMS_HOST_NAME'))
      define('MCMS_HOST_NAME', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');

    define('MCMS_SITE_FOLDER', self::find_site_folder(MCMS_HOST_NAME));

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

  public static function run($message = 'ru.molinos.cms.start')
  {
    self::setup();

    $ctx = new Context();
    $ctx->registry->unicast($message, array($ctx));

    header('Content-Type: text/plain; charset=utf-8');
    die(printf('Nobody handled "%s" and it took %f seconds.', $message, microtime(true) - MCMS_START_TIME));
  }
}
