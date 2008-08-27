<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

require(dirname(__FILE__) .'/lib/bootstrap.php');

// Проверка окружения.  Если что не так — выводит сообщение об ошибке.
mcms::check();

$me = new RequestController();

$output = $me->getContent();

header('Content-Length: '. strlen($output));
die($output);
