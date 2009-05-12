<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Config
{
  private static $instance = null;
  private $data = null;
  private $path = null;

  public function __construct()
  {
    $this->path = $this->findFile();
  }

  private function readData()
  {
    if (!is_readable($this->path))
      $this->data = array();
    else
      $this->data = include $this->path;
  }

  private function findFile()
  {
    return MCMS_SITE_FOLDER . DIRECTORY_SEPARATOR . 'config.php';
  }

  private function __isset($varname)
  {
    return null !== $this->__get($varname);
  }

  private function __get($varname)
  {
    if (null === $this->data)
      $this->readData();

    $res = array_key_exists($varname, $this->data)
      ? $this->data[$varname]
      : null;

    switch ($varname) {
    case 'db':
      if (0 === strpos($res, 'sqlite:'))
        $res = 'sqlite:' . $this->getDirName() . DIRECTORY_SEPARATOR . substr($res, 7);
      break;
    }

    return $res;
  }

  private function __set($varname, $value)
  {
    if (empty($varname))
      throw new InvalidArgumentException(t('Не указано имя параметра.'));

    if (null === $this->data)
      $this->readData();

    if (false === strpos($varname, '_'))
      $this->data[$varname] = $value;
    else {
      list($a, $b) = explode('_', $varname, 2);
      if (!array_key_exists($a, $this->data))
        $this->data[$a] = array();
      elseif (!is_array($this->data[$a]))
        throw new RuntimeException(t('Ключ %name уже занят строчным параметром.', array(
          '%name' => $a,
          )));
      $this->data[$a][$b] = $value;
    }
  }

  private function __unset($varname)
  {
    if (null === $this->data)
      $this->readData();

    if (array_key_exists($varname, $this->data))
      unset($this->data[$varname]);
  }

  public function isok()
  {
    return file_exists($this->path);
  }

  public function reload()
  {
    if (empty($this->data))
      $this->readData();
  }

  public function write()
  {
    ksort($this->data);
    os::writeArray($this->path, $this->data, true);
  }

  public function isWritable()
  {
    return is_writable($this->path);
  }

  public function getBaseName()
  {
    return preg_replace('/\.(ini|config\.php)$/', '', $this->path);
  }

  public function getDirName()
  {
    return dirname($this->path);
  }

  /**
   * Возвращает полный путь, описанный определённым ключом.
   */
  public function getPath($key)
  {
    if (null === ($value = $this->$key))
      return null;
    else
      return $this->getRealPath($value);
  }

  private function getRealPath($shortPath)
  {
    return MCMS_ROOT . DIRECTORY_SEPARATOR . $this->getDirName() . DIRECTORY_SEPARATOR . $shortPath;
  }
}
