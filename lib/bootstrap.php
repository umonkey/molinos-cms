<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2

define('BEBOP_VERSION', '8.05.BUILDNUMBER');

// Выходим на корневой каталог админки.
chdir(dirname(dirname(__FILE__)));

// Некоторые файлы загружаем принудительно, т.к. без них работать не получится.
// require(dirname(__FILE__).'/modules/cache/bebopcache.class.php');
require(dirname(__FILE__).'/modules/config/class.bebopconfig.php');
// require(dirname(__FILE__).'/modules/pdo/pdo_singleton.class.php');

// Загружаем основные файлы.
require(dirname(__FILE__) .'/bebop_functions.php');
require(dirname(__FILE__) .'/bebop_autoload.php');
require(dirname(__FILE__) .'/pbs_exception_handler.php');
