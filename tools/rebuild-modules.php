<?php

require dirname(__FILE__) .'/../lib/bootstrap.php';

function rebuild_modules($dir)
{
  if (!is_dir($dir))
    mkdir($dir);

  $distinfo = array();

  foreach (glob(os::path('lib', 'modules', '*', 'module.ini')) as $inifile) {
    $module = basename(dirname($inifile));
    $ini = ini::read($inifile);

    foreach (array('section', 'priority', 'version', 'filename', 'name') as $k) {
      if (!array_key_exists($k, $ini)) {
        printf("warning: %s has no '%s' key, module ignored.\n", $module, $k);
        $ini = null;
        break;
      }
    }

    if (null !== $ini) {
      $distinfo[$module] = $ini;
      $zipname = $module . '-' . $ini['version'] . '.zip';

      zip::fromFolder(os::path($dir, $zipname), dirname($inifile));
    }
  }

  if (!empty($distinfo)) {
    $header =
      "; Дата создания: " . mcms::now() . "\n"
      . ";\n"
      . "; Секции:\n"
      . ";   core = необходимая функциональность\n"
      . ";   base = базовая функциональность\n"
      . ";   admin = функции для администрирования\n"
      . ";   service = сервисные функции\n"
      . ";   blog = работа с блогами\n"
      . ";   spam = борьба со спамом\n"
      . ";   commerce = электронная коммерция\n"
      . ";   interaction = интерактив\n"
      . ";   performance = производительность\n"
      . ";   multimedia = мультимедийные функции\n"
      . ";   syndication = обмен данными между сайтами\n"
      . ";   templating = работа с шаблонами\n"
      . ";   visual = визуальные редакторы\n";

    ini::write(os::path($dir, 'modules.ini'), $distinfo, $header);
  }
}

rebuild_modules('modules');

printf("Done.\n");
