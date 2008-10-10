<?php

// Текущая версия.
define('MCMS_VERSION', '8.05.6194');

// Полный путь к папке, в которую установлена CMS.
define('MCMS_ROOT', dirname(realpath(__FILE__)));

// Работа в нормальном режиме.
if (file_exists($bootstrap = dirname(__FILE__) .'/lib/'. MCMS_VERSION .'/loader.php'))
  require_once $bootstrap;

// Работа прямо из git.
elseif (file_exists($bootstrap = dirname(__FILE__) .'/lib/loader.php'))
  require_once $bootstrap;

else {
  header('HTTP 500 Internal Server Error');
  header('Content-Type: text/plain; charset=utf-8');
  die('Molinos CMS loader not found in lib/'. MCMS_VERSION ."\n");
}
