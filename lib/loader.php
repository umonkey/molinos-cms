<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2

define('MCMS_START_TIME', microtime(true));

// Некоторые файлы загружаем принудительно, т.к. без них работать не получится.
// require(MCMS_ROOT .'/lib/modules/cache/class.bebopcache.php');
require(MCMS_LIB .'/modules/base/class.config.php');

// Проверка добавлена для того, чтобы не получить дурацкое
// сообщение о неизвестном классе; мы сами сообщаем об отсутствии
// PDO в RequestController::checkSettings().
if (class_exists('PDO', false))
  require(MCMS_LIB .'/modules/pdo/class.pdo_singleton.php');

// Загружаем основные файлы.
require(MCMS_LIB .'/bebop_functions.php');
require(MCMS_LIB .'/bebop_autoload.php');
