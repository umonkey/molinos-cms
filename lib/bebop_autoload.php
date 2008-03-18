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

      $output = "There was an attempt to access an undefined class {$class_name}.\n\n";
      $output .= var_export($map, true);

      die($output);
    }
}

spl_autoload_register('bebop_autoload');
