<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class BebopConfig
{
    private static $instance = null;
    private $data = null;
    private $path = null;
    private $isok = false;

    private function __construct()
    {
    }

    static public function getInstance(User $user = null)
    {
        if (self::$instance === null)
            self::$instance = new BebopConfig();
        return self::$instance;
    }

    private function getFileName()
    {
      if ($this->path !== null and 'default.php' != basename($this->path))
        return $this->path;

      $prefix = getcwd() .'/conf/';

      $result = array();

      // Строим полный путь к файлу.
      for ($parts = explode('.', $_SERVER['HTTP_HOST']); !empty($parts); $result[] = array_pop($parts));

      // Перебираем возможные варианты, отсекая по одному элементу.
      while (!empty($result)) {
        if (is_readable($this->path = $prefix . join('.', $result) .'.ini'))
          return $this->path;
        if (is_readable($this->path = $prefix . join('.', $result) .'.php'))
          return $this->path;
        array_pop($result);
      }

      // Дошли до самого конца: пытаемся открыть дефолтный файл.
      if (is_readable($this->path = $prefix .'default.ini'))
        return $this->path;
      if (is_readable($this->path = $prefix .'default.php'))
        return $this->path;
    }

    private function readData()
    {
      $file = $this->getFileName();

      if (!is_readable($file)) {
        $this->data = array();
      }

      elseif (substr($file, -4) == '.ini') {
        $this->data = parse_ini_file($file);
        $this->isok = true;
      }

      else {
        require($file);
        $this->data = $config;
        $this->isok = true;
      }
    }

    private function __isset($varname)
    {
      if ($this->data === null)
        $this->readData();

      return array_key_exists($varname, $this->data);
    }

    private function __get($varname)
    {
      if ($this->data === null)
        $this->readData();

      $res = array_key_exists($varname, $this->data) ? $this->data[$varname] : null;

      if (null === $res) switch ($varname) {
      case 'tmpdir':
        $res = 'tmp';
        break;
      case 'filestorage':
        $res = 'storage';
        break;
      }

      return $res;
    }

    private function __set($varname, $value)
    {
      throw new InvalidArgumentException('Configuration is read only.');
    }

    private function __unset($varname)
    {
      throw new InvalidArgumentException('Configuration is read only.');
    }

    public function isok()
    {
      return $this->isok;
    }

    public function reload()
    {
      if (empty($this->data))
        $this->readData();
    }
}
