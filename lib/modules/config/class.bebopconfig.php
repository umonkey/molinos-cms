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
        $this->data = parse_ini_file($file, true);
        $this->isok = true;
      }
      else {
        require_once($file);
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
      throw new InvalidArgumentException(t('Для изменения конфигурации используется метод set().'));
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

    public function set($key, $value, $section = null)
    {
      if (empty($key))
        throw new InvalidArgumentException(t('Не указано имя параметра.'));

      if (null != $section) {
        if (array_key_exists($section, $this->data) and is_array($this->data[$section]))
          $this->data[$section][$key] = $value;
        else
          $this->data[$section] = array($key => $value);
      } else
        $this->data[$key] = $value;
    }

    public function write()
    {
      $output =
        "; vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 ft=dosini:\n"
        .";\n"
        ."; Do not edit this file by hand, hex said something bad might happen.\n"
        ."\n";

      // Сначала надо выгрузить все "простые" параметры, чтобы они не попали в какую-нибудь секцию.
      foreach ($this->data as $k => $v)
        if (!is_array($v) && !empty($v))
          $output .= "{$k} = {$v}\n";

      foreach ($this->data as $k => $v)
        // Массив в параметрах есть — теперь пишем секцию.
        if (is_array($v)) {
          $str = $this->dumpSection($v);
          if (!empty($str))
            $output .= "[{$k}]\n" . $str;
      }

      if (!file_put_contents($this->path, $output))
        // А такое вообще может быть?
        throw new RuntimeException(t("Не удалось сохранить конфигурационный файл в {$this->path}."));
    }

    private function dumpSection(array $data)
    {
      $output = '';

      foreach ($data as $k => $v)
        if (!empty($v) and !is_array($v))
          $output .= sprintf("%s = %s\n", $k, $v);

      return $output;
    }
}
