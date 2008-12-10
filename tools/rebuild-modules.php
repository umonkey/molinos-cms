<?php

require dirname(__FILE__) .'/../lib/bootstrap.php';

if (!is_dir('modules'))
  mkdir('modules');

foreach (glob(os::path('lib', 'modules', '*', 'module.ini')) as $inifile) {
  $module = basename(dirname($inifile));
  $ini = ini::read($inifile);
  $zipname = $module . '-' . $ini['version'] . '.zip';

  zip::fromFolder(os::path('modules', $zipname), dirname($inifile));
}

printf("Done.\n");
