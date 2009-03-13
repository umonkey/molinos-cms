<?php

@include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'codecache.php';

// Для включения локального лога расскомментировать:
// ini_set('error_log', dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR . 'php.log');

// Расскоментировать для трассировки вызовов.
// xdebug_start_trace(dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR . "xdebug.log");

if (version_compare(PHP_VERSION, "5.2.0", "<")) {
  header('Content-Type: text/plain; charset=utf-8');
  die('Для работы Molinos CMS требуется PHP версии 5.2.0, или более новый.');
}

// Основной загрузчик. Инициализирует реестр, запускает обработку запроса.
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR
  . 'modules' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'class.loader.php';

Loader::run();
