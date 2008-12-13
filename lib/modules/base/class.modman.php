<?php

class modman
{
  /**
   * Возвращает путь к файлу, хранящему информацию о модулях.
   */
  public static function getInfoPath()
  {
    return os::path(mcms::config('tmpdir'), 'modules.ini');
  }

  /**
   * Возвращает информацию обо всех модулях.
   */
  public static function getAllModules()
  {
    if (!file_exists($path = self::getInfoPath()))
      $ini = array();

    else {
      $ini = ini::read($path);

      foreach ($ini as $k => $v) {
        if (!empty($v['name.ru']))
          $ini[$k]['name'] = $v['name.ru'];
        $ini[$k]['installed'] = mcms::ismodule($k);
      }
    }

    return $ini;
  }

  /**
   * Возвращает информацию о модулях, которые можно обновить.
   */
  public static function getUpdatedModules()
  {
    $result = array();
    $local = self::getLocalModules();

    foreach (self::getAllModules() as $name => $available) {
      // У нас такого нет.
      if (!array_key_exists($name, $local))
        continue;

      // Модуль выключен.
      if (!mcms::ismodule($name))
        continue;

      if (version_compare($available['version'], $local[$name]['version'], '>'))
        $result[$name] = $available;
    }

    return $result;
  }

  /**
   * Возвращает информацию о локальных модулях.
   */
  public static function getLocalModules()
  {
    $result = array();

    foreach (glob(os::path('lib', 'modules', '*', 'module.ini')) as $file) {
      $ini = ini::read($file);
      $name = basename(dirname($file));
      $ini['enabled'] = mcms::ismodule($name);
      $ini['version.local'] = $ini['version'];
      unset($ini['version']);
      if (!empty($ini['name.ru']))
        $ini['name'] = $ini['name.ru'];
      $ini['configurable'] = count(mcms::getImplementors('iModuleConfig', $name)) > 0;
      $result[$name] = $ini;
    }

    return $result;
  }
}
