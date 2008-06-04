<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2

if (empty($_SERVER['DOCUMENT_ROOT']))
  $_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__) .'/../');

if (empty($_SERVER['HTTP_HOST']))
  $_SERVER['HTTP_HOST'] = 'localhost';

define('MCMS_ROOT', dirname(dirname(__FILE__)));
if (!defined('MCMS_PATH'))
  define('MCMS_PATH', '/'. trim(str_replace($_SERVER['DOCUMENT_ROOT'], '', MCMS_ROOT), '/'));
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
