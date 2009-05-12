<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Config
{
  private static $instance = null;
  private $data = null;
  private $path = null;

  public function __construct()
  {
    $this->path = MCMS_SITE_FOLDER . DIRECTORY_SEPARATOR . 'config.ini';
  }

  private function readData()
  {
    if (!is_readable($this->path))
      $this->data = array();
    else
      $this->data = ini::read($this->path);
  }

  private function __isset($varname)
  {
    return null !== $this->__get($varname);
  }

  private function __get($varname)
  {
    if (null === $this->data)
      $this->readData();

    // Обратная совместимость.
    // TODO: переписать исходные вызовы.
    switch ($varname) {
    case 'files':
      $varname = 'attachment_storage';
      break;
    case 'files_ftp':
      $varname = 'attachment_ftp';
      break;
    }

    if (2 == count($parts = explode('_', $varname, 2)))
      $res = isset($this->data[$parts[0]][$parts[1]])
        ? $this->data[$parts[0]][$parts[1]]
        : null;
    else
      $res = array_key_exists($parts[0], $this->data)
        ? $this->data[$parts[0]]
        : array();

    switch ($varname) {
    case 'db_dsn':
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
    ini::write($this->path, $this->data);
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
