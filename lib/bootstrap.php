<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2

// Выходим на корневой каталог админки.
chdir(dirname(dirname(__FILE__)));

// Загружаем основные файлы.
require_once(dirname(__FILE__) .'/bebop_functions.php');
require_once(dirname(__FILE__) .'/bebop_autoload.php');
require_once(dirname(__FILE__) .'/pbs_exception_handler.php');
