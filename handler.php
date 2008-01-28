<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2

require(dirname(__FILE__) .'/lib/bootstrap.php');

if (bebop_is_debugger() and $_SERVER['REQUEST_URI'] == '/info.php') {
  die(phpinfo());
}

$me = new RequestController();

$output = $me->getContent();

header('Content-Length: '. strlen($output));
die($output);
