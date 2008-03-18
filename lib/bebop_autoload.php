<?php

function bebop_autoload($class_name)
{
    static $map = null;

    if (null === $map)
      $map = mcms::getClassMap();

    $k = strtolower($class_name);

    if (array_key_exists($k, $map)) {
      include($map[$k]);
    } else {
      header('Content-Type: text/plain; charset=utf-8');

      print "There was an attempt to access an undefined class {$class_name}.\n\n";
      print var_export($map, true) ."\n";
      print "--- backtrace ---\n";

      debug_print_backtrace();
      die();
    }
}

spl_autoload_register('bebop_autoload');
