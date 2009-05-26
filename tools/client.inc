<?php
/**
 * Инициализатор окружения для консольных скриптов Molinos CMS.
 *
 */

chdir(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..');

@include 'localsettings.php';

/**
 * Загрузка ядра.
 */
require 'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
  . 'core' . DIRECTORY_SEPARATOR . 'class.loader.php';

/**
 * Инициализация констант.
 */
Loader::setup();
