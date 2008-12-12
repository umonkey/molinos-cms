<?php

// Расскоментировать для трассировки вызовов.
// xdebug_start_trace(dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR . "xdebug.log");

if (version_compare(PHP_VERSION, "5.2.0", "<"))
  die('Для работы Molinos CMS требуется PHP версии 5.2.0, или более новый.');

require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'bootstrap.php');

mcms::run();
