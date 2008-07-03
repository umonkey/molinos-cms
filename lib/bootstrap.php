<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2

// Нет адреса — запуск из консоли, нужно в основном для тестов.
if (empty($_SERVER['HTTP_HOST'])) {
  $_SERVER['HTTP_HOST'] = 'localhost';

  define('MCMS_ROOT', dirname(dirname(__FILE__)));
}

// Обычная ситуация — запуск через веб.
else {
  define('MCMS_ROOT', dirname(dirname(__FILE__)));
}

define('MCMS_START_TIME', microtime(true));

// Выходим на корневой каталог админки.
chdir(MCMS_ROOT);

// Некоторые файлы загружаем принудительно, т.к. без них работать не получится.
// require(MCMS_ROOT .'/lib/modules/cache/class.bebopcache.php');
require(MCMS_ROOT .'/lib/modules/config/class.bebopconfig.php');

// Проверка добавлена для того, чтобы не получить дурацкое
// сообщение о неизвестном классе; мы сами сообщаем об отсутствии
// PDO в RequestController::checkSettings().
if (class_exists('PDO', false))
  require(MCMS_ROOT .'/lib/modules/pdo/class.pdo_singleton.php');

// Загружаем основные файлы.
require(MCMS_ROOT .'/lib/bebop_functions.php');
require(MCMS_ROOT .'/lib/bebop_autoload.php');
