<?php

require dirname(__FILE__) .'/../lib/bootstrap.php';

$ini = array();

foreach (glob(os::path('lib', 'modules', '*', 'module.ini')) as $file) {
  $module = basename(dirname($file));

  $tmp = ini::read($file);

  foreach (array('section', 'priority', 'version', 'filename', 'name') as $k)
    if (!array_key_exists($k, $ini[$module])) {
      printf("warning: %s has no '%s' key, module ignored.\n", $module, $k);
      $tmp = null;
      break;
    }

  if (null !== $tmp)
    $ini[$module] = $tmp;
}

$header = "
; Секции:
;   core = необходимая функциональность
;   base = базовая функциональность
;   admin = функции для администрирования
;   service = сервисные функции
;   blog = работа с блогами
;   spam = борьба со спамом
;   commerce = электронная коммерция
;   interaction = интерактив
;   performance = производительность
;   multimedia = мультимедийные функции
;   syndication = обмен данными между сайтами
;   templating = работа с шаблонами
;   visual = визуальные редакторы
";

ini::write('modules.ini', $ini, $header);
