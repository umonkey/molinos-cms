<?php

if (empty($argv[1])) {
  printf("Usage: %s hostname\n", basename($argv[0]));
  exit(1);
}

define('MCMS_HOST', $argv[1]);
define('MCMS_REQUEST_URI', '?q=cron.rpc');

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'index.php';
