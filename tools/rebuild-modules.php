<?php

require dirname(__FILE__) .'/../lib/bootstrap.php';

function zip_folder($filename, $path)
{
  $path = rtrim($path, DIRECTORY_SEPARATOR);

  printf("%s << %s\n", $filename, $path);

  $z = new ZipArchive();
  $z->open($filename, ZIPARCHIVE::OVERWRITE);

  foreach (os::listFiles($path) as $file)
    $z->addFile($file, substr($file, strlen($path) + 1));

  $z->close();
}

if (!is_dir('modules'))
  mkdir('modules');

foreach (glob(os::path('lib', 'modules', '*', 'module.ini')) as $inifile) {
  $module = basename(dirname($inifile));
  $ini = ini::read($inifile);
  $zipname = $module . '-' . $ini['version'] . '.zip';

  zip_folder(os::path('modules', $zipname), dirname($inifile));
}

printf("Done.\n");
