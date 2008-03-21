<?php

function bebop_autoload($class_name)
{
    static $map = null;

    if (null === $map)
      $map = mcms::getClassMap();

    $k = strtolower($class_name);

    if (array_key_exists($k, $map)) {
      if (!is_readable($map[$k]))
        mcms::fatal("{$class_name} is in a file which is read-protected: {$map[$k]}");

      include($map[$k]);

      $isif = (substr($class_name, 0, 1) === 'i');

      if ($isif and !in_array($class_name, get_declared_interfaces()))
        mcms::fatal("There is no {$class_name} interface in {$map[$k]}", array('declared_interfaces' => get_declared_interfaces()));
      elseif (!$isif and !in_array($class_name, get_declared_classes()))
        mcms::fatal("There is no {$class_name} class in {$map[$k]}", array('declared_classes' => get_declared_classes()));
    }

    else {
      mcms::fatal("There was an attempt to access an undefined class {$class_name}.");
    }
}

spl_autoload_register('bebop_autoload');
