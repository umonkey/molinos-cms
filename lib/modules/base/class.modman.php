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
    $result = self::getAllModules();

    foreach ($result as $name => $info)
      if (!self::canUpdateModule($name, $info))
        unset($result[$name]);

    return $result;
  }

  private static function canUpdateModule($name, array $info)
  {
    if (!mcms::ismodule($name))
      return false;

    if (empty($info['url']))
      return false;

    if (version_compare($info['version.local'], $info['version'], '>='))
      return false;

    return true;
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

  /**
   * Обновление информации о доступных модулях.
   */
  public static function updateDB()
  {
    $modules = array();

    foreach (self::getSources() as $url) {
      if (($file = http::fetch($url, http::NO_CACHE))) {
        $ini = ini::read($file);

        if (empty($ini['url_prefix']) or !is_string($ini['url_prefix']))
          $ini['url_prefix'] = 'http://molinos-cms.googlecode.com/files/';

        foreach ($ini as $k => $v) {
          if (is_array($v)) {
            if (empty($v['url']))
              $v['url'] = $ini['url_prefix'] . $v['filename'];

            if (!array_key_exists($k, $modules))
              $modules[$k] = $v;
            elseif (version_compare($v['version'], $modules[$k]['version'], '>'))
              $modules[$k] = $v;
          }
        }
      }
    }

    foreach ($modules as $k => $v) {
      $local = os::path('lib', 'modules', $k, 'module.ini');

      if (file_exists($local)) {
        $ini = ini::read($local);
        $v['version.local'] = $ini['version'];
      }

      ksort($v);
      $modules[$k] = $v;
    }

    mcms::flog('modman', 'module info updated, ' . count($modules) . ' module(s) available.');

    ini::write(self::getInfoPath(), $modules);
  }

  /**
   * Обновление конкретного модуля.
   */
  public static function updateModule($name)
  {
    $db = self::getAllModules();

    if (!array_key_exists($name, $db))
      throw new RuntimeException(t('Нет информации о модуле %name.', array(
        '%name' => $name,
        )));

    if (empty($db[$name]['url'])) {
      mcms::flog('modman', "no url for module {$name}, not updated.");
      return false;
    }

    $head = http::head($url = $db[$name]['url']);

    if (200 != $head['_status']) {
      mcms::flog('modman', 'updateModule: file not found: ' . $url);
      return false;
    }

    $tmp = http::fetch($url);

    foreach (array('md5' => 'md5_file', 'sha1' => 'sha1_file') as $k => $func) {
      if (!empty($db[$name][$k]) and $db[$name][$k] != $func($tmp)) {
        mcms::flog('modman', $k . ' hash mismatch for ' . $url);
        return false;
      }
    }

    zip::unzipToFolder($tmp, os::path('lib', 'modules', $name));

    return true;
  }

  /**
   * Возвращает список источников обновлений.
   */
  public static function getSources()
  {
    if (!is_array($urls = mcms::config('sources')))
      $urls = array();

    $default = 'http://molinos-cms.googlecode.com/svn/dist/' . mcms::version(mcms::VERSION_RELEASE) . '/modules.ini';

    if (!in_array($default, $urls))
      $urls[] = $default;

    return $urls;
  }
}
