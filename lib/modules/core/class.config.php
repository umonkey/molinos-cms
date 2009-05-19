<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Config extends ArrayObject
{
  private $path;

  public function __construct($fileName = 'config.yml')
  {
    $this->path = MCMS_SITE_FOLDER . DIRECTORY_SEPARATOR . $fileName;

    if (!file_exists($this->path))
      $data = array();
    elseif (!is_readable($this->path))
      throw new RuntimeException($fileName . ' is not readable.');
    else {
      $cache = cache::getInstance();

      if (!is_array($data = $cache->config)) {
        $data = Spyc::YAMLLoad($this->path);
        $cache->config = $data;
      }
    }

    return parent::__construct($data);
  }

  public function save()
  {
    self::purge($this);

    if (file_put_contents($this->path, Spyc::YAMLDump($this)))
      cache::getInstance()->config = $this;
    return $this;
  }

  public function getDirName()
  {
    return dirname($this->path);
  }

  public function get($keyName, $default = null)
  {
    $path = explode('/', $keyName);
    $root = $this;

    while (!empty($path)) {
      $em = array_shift($path);

      if (!isset($root[$em]))
        return $default;

      $root = $root[$em];

      if (!empty($path) and !is_array($root))
        return $default;
    }

    return $root;
  }

  public function getInt($keyName, $default = 0)
  {
  }

  public function getPath($keyName, $default = null)
  {
    if (!($value = $this->get($keyName, $default)))
      mcms::fatal('Неизвестный путь: ' . $keyName . '.');

    $result = MCMS_SITE_FOLDER . DIRECTORY_SEPARATOR . $value;

    return $result;
  }

  public function getString($keyName, $default = null)
  {
  }

  public function getArray($keyName, $default = array())
  {
  }

  /**
   * Удаляет пустые массивы.
   */
  public static function purge(&$branch)
  {
    foreach ($branch as $k => $v) {
      if (is_array($v)) {
        if (empty($v))
          unset($branch[$k]);
        else
          self::purge($branch[$k]);
      }
    }
  }

  public function set($keyName, $value)
  {
    $root = &$this;
    $path = explode('/', $keyName);

    while (count($path) > 1) {
      $em = array_shift($path);
      if (!isset($root[$em]))
        $root[$em] = array();
      $root = &$root[$em];
    }

    $root[array_shift($path)] = $value;
  }

  /**
   * Проверяет существование конфига.
   */
  public function isOk()
  {
    return file_exists($this->path);
  }
}
