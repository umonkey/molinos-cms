<?php

define('COUNT', 1000);

$data = file_get_contents(__FILE__);

foreach (dba_handlers() as $handler) {
  if ('inifile' == $handler)
    continue;

  $filename = 'cache.' . $handler;
  printf("testing %s\n", $handler);

  if (file_exists($filename))
    unlink($filename);

  if (!($db = @dba_open($filename, 'cd', $handler))) {
    printf(" - open failed\n");
    continue;
  }

  $time = microtime(true);
  for ($idx = 0; $idx < COUNT; $idx++)
    dba_replace('key' . $idx, $data, $db);
  $time1 = microtime(true) - $time;
  printf(" + %u writes = %f sec (%f w/sec)\n", COUNT, $time1, $time1 / COUNT);

  $time = microtime(true);
  for ($idx = 0; $idx < COUNT; $idx++) {
    if ($data !== dba_fetch('key' . $idx, $db)) {
      printf(" - error\n");
      continue 2;
    }
  }
  $time2 = microtime(true) - $time;
  printf(" + %u reads = %f sec (%f r/sec)\n", COUNT, $time2, $time2 / COUNT);

  dba_close($db);
}

