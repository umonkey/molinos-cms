<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2

// Нет адреса — запуск из консоли, нужно в основном для тестов.
if (empty($_SERVER['HTTP_HOST'])) {
  $_SERVER['HTTP_HOST'] = 'localhost';

  define('MCMS_ROOT', dirname(dirname(__FILE__)));
  define('MCMS_PATH', '/');
}

// Обычная ситуация — запуск через веб.
else {
  define('MCMS_ROOT', dirname(dirname(__FILE__)));
  define('MCMS_PATH', rtrim(preg_replace('#/lib/modules/.*#', '', str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['SCRIPT_NAME']))), '/') .'/');

  // FIXME: ^^^ этот вот preg_replace() мне не нравится, но нужен, чтобы если
  // сайт расположен в папке /test/, но обращение идёт не к index.php, а к файлу
  // внутри модуля, скажем, /test/lib/modules/xyz.php, то без этого RE мы получим
  // вместо пути к админке /test/lib/modules/, что нам не нужно.
}

define('MCMS_START_TIME', microtime(true));

// Выходим на корневой каталог админки.
chdir(MCMS_ROOT);

// Некоторые файлы загружаем принудительно, т.к. без них работать не получится.
// require(MCMS_ROOT .'/lib/modules/cache/class.bebopcache.php');
require(MCMS_ROOT .'/lib/modules/config/class.bebopconfig.php');
require(MCMS_ROOT .'/lib/modules/pdo/class.pdo_singleton.php');

// Загружаем основные файлы.
require(MCMS_ROOT .'/lib/bebop_functions.php');
require(MCMS_ROOT .'/lib/bebop_autoload.php');
