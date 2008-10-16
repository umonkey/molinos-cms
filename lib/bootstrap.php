<?php

// Текущая версия.
define('MCMS_VERSION', '8.05.6195');

// Полный путь к папке, в которую установлена CMS.
define('MCMS_ROOT', realpath(dirname(realpath(__FILE__)) .'/..'));

// Работа в нормальном режиме.
if (file_exists($bootstrap = dirname(__FILE__) .'/'. MCMS_VERSION .'/loader.php'))
  ;

// Работа прямо из git.
elseif (file_exists($bootstrap = dirname(__FILE__) .'/loader.php'))
  ;

else {
  header('HTTP 500 Internal Server Error');
  header('Content-Type: text/plain; charset=utf-8');
  die('Molinos CMS loader not found in lib/'. MCMS_VERSION ."\n");
}

// Путь к системным файлам.
define('MCMS_LIB', dirname($bootstrap));

require $bootstrap;
