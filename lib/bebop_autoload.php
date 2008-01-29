<?php

define('BEBOP_VERSION', '8.02.BUILDNUMBER');

// We load cache before everything else because
// it helps us reduce the metadata reading time.
require_once(dirname(__FILE__).'/modules/cache/cache.php');
require_once(dirname(__FILE__).'/modules/config/config.php');
require_once(dirname(__FILE__).'/modules/pdo/pdo.php');

function bebop_get_module_map()
{
  if (is_array($map = mcms::cache('module_map')) and empty($_GET['reload']))
    return $map;

  // Загружаем список активных модулей.
  $enabled = PDO_Singleton::getInstance()->getResultsV("name", "SELECT `n`.`id`, `r`.`name` FROM `node` `n` INNER JOIN `node__rev` `r` ON `r`.`rid` = `n`.`rid` WHERE `n`.`class` = 'moduleinfo' AND `n`.`published` = 1");

  foreach (glob(dirname(__FILE__) .'/modules/'.'*') as $path) {
    $tmp = explode('/', $path);
    $module = array_pop($tmp);
    $mpath = $path .'/'. $module;

    if (is_readable($mpath .'.info')) {
      $info = file($mpath .'.info');

      $map[$module]['file'] = $mpath .'.php';

      foreach ($info as $line) {
        $line = trim($line);

        if (empty($line) or substr($line, 0, 1) == ';')
          continue;

        if (preg_match('/^([a-z]+)(\[([a-z]+)\]){0,1} = (.*)$/i', $line, $m)) {
          if ($m[1] == 'classes' or $m[1] == 'interface')
            $value = 'array("'. str_replace(', ', '", "', $m[4]) .'")';
          else
            $value = '"'. $m[4] .'"';

          if (empty($m[3]))
            $code = '$map["'. $module .'"]["'. $m[1] .'"] = '. $value .';';
          else
            $code = '$map["'. $module .'"]["'. $m[1] .'"]["'. $m[3] .'"] = '. $value .';';

          eval($code);
        }
      }

      $map[$module]['enabled'] = ((!empty($map[$module]['group']) and 'core' == $map[$module]['group']) or in_array($module, $enabled)) ? true : false;
    }
  }

  ksort($map);

  mcms::cache('module_map', $map);

  return $map;
}

function bebop_autoload($class_name)
{
    static $arr = null;

    if (null === $arr) {
      foreach (bebop_get_module_map() as $module => $info) {
        if (empty($info['classes']) or !is_array($info['classes'])) {
          print "Malformed metadata for {$module}:<br /><pre>";
          die(var_dump($info));
        }

        if (empty($info['enabled']))
          continue;

        foreach ($info['classes'] as $class)
          $arr[$class] = $info['file'];
      }
    }

    if (array_key_exists($class_name, $arr)) {
      include_once($arr[$class_name]);
      return true;
    } elseif (false) {
      print "Could not find container for {$class_name}, map follows.<br />";
      die(var_dump($arr));
    }
}

spl_autoload_register('bebop_autoload');
