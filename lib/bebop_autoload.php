<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

require_once(MCMS_LIB .'/modules/base/class.mcms.php');

function bebop_autoload($class_name)
{
  static $map = null;

  if (null === $map)
    $map = mcms::getClassMap();

  $k = strtolower($class_name);

  if (array_key_exists($k, $map)) {
    if (!file_exists($map[$k]))
      throw new RuntimeException("{$class_name} is in a file which "
        ."does not exist: {$map[$k]}");
    elseif (!is_readable($map[$k]))
      throw new RuntimeException("{$class_name} is in a file which "
        ."is read-protected: {$map[$k]}");

    include($map[$k]);

    $isif = (substr($class_name, 0, 1) === 'i');

    if ($isif and !in_array($class_name, get_declared_interfaces()))
      throw new RuntimeException("There is no {$class_name} interface "
        ."in {$map[$k]}.\nAPC freaks out this way sometimes.");
    elseif (!$isif and !in_array($class_name, get_declared_classes()))
      throw new RuntimeException("There is no {$class_name} class "
        ."in {$map[$k]}");
  }
}

spl_autoload_register('bebop_autoload');
