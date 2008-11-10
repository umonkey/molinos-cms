<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:
// some changes.

class Config
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
            self::$instance = new Config();
        return self::$instance;
    }

    private function getFileName()
    {
      if ($this->path !== null and 'default.php' != basename($this->path))
        return $this->path;

      $prefix = MCMS_ROOT . DIRECTORY_SEPARATOR .'conf'. DIRECTORY_SEPARATOR;

      $result = array();

      // Строим полный путь к файлу.
      for ($parts = explode('.', $_SERVER['HTTP_HOST']); !empty($parts); $result[] = array_pop($parts));

      // Перебираем возможные варианты, отсекая по одному элементу.
      while (!empty($result)) {
        if (is_readable($this->path = $prefix . join('.', $result) .'.ini'))
          return $this->path;
        array_pop($result);
      }

      // Дошли до самого конца: пытаемся открыть дефолтный файл.
      if (is_readable($this->path = $prefix .'default.ini'))
        return $this->path;

      // Дефолтный не найден, пытаемся использовать демо.
      if (in_array('sqlite', PDO::getAvailableDrivers())) {
        if (is_readable($this->path = $prefix .'default.ini.dist')) {
          // Копируем пример в нормальный конфиг.
          if (is_writable(dirname($this->path))) {
            copy($this->path, $tmp = $prefix .'default.ini');
            $this->path = $tmp;
          } else {
            $this->path = null;
          }

          return $this->path;
        }
      }

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

      $this->data['cleanurls'] = !empty($_GET['__cleanurls']);
    }

    private function __isset($varname)
    {
      return null !== $this->__get($varname);
    }

    private function __get($varname)
    {
      if ($this->data === null)
        $this->readData();

      $res = null;

      if (array_key_exists($varname, $this->data))
        $res = $this->data[$varname];
      elseif (count($tmp = explode('_', $varname, 2)) == 2) {
        if (!empty($this->data[$tmp[0]][$tmp[1]]))
          $res = $this->data[$tmp[0]][$tmp[1]];
      }

      if (null === $res) {
        switch ($varname) {
        case 'tmpdir':
          $res = 'tmp';
          break;
        case 'filestorage':
          $res = 'storage';
          break;
        case 'filename':
          $res = basename($this->path);
          break;
        }
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

      if ($this->data === null)
        $this->readData();

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
        ."; Written by Molinos.CMS v". mcms::version() ."\n"
        ."\n";

      // Сначала надо выгрузить все "простые" параметры, чтобы они не попали в какую-нибудь секцию.
      foreach ($this->data as $k => $v)
        if (!is_array($v) && !empty($v) and 'cleanurls' != $k)
          $output .= "{$k} = {$v}\n";

      foreach ($this->data as $k => $v) {
        // Массив в параметрах есть — теперь пишем секцию.
        if (is_array($v)) {
          $str = $this->dumpSection($v);
          if (!empty($str))
            $output .= "\n[{$k}]\n" . $str;
        }
      }

      if (!strlen($path = $this->getFileName()))
        throw new RuntimeException(t('Конфигурационный файл не определён.'));

      if (!file_exists($path))
        mcms::mkdir(dirname($path), 'Не удалось создать папку для конфигурационных файлов (%path).');

      if (!is_writable(dirname($path)))
        throw new RuntimeException(t('Конфигурационный файл закрыт для записи (%path).', array('%path' => $path)));

      $this->backup($path);

      if (!file_put_contents($path, $output))
        // А такое вообще может быть?
        throw new RuntimeException(t("Не удалось сохранить конфигурационный файл в {$this->path}."));

      // FIXME: если файл существует, и не наш — получаем нотис.
      // chmod($path, 0660);
    }

    private function dumpSection(array $data)
    {
      $output = '';

      foreach ($data as $k => $v) {
        if (is_array($v))
          $value = join(', ', $v);
        elseif (0 === $v)
          $value = '0';
        elseif (empty($v))
          $value = 'off';
        else
          $value = $v;

        $output .= sprintf("%s = %s\n", $k, $value);
      }

      return $output;
    }

    private function backup($path)
    {
      $backup = $path .'.lkg';

      if (file_exists($backup))
        unlink($backup);

      if (file_exists($path))
        copy($path, $backup);
    }

    public function isWritable()
    {
      return is_writable($this->getFileName());
    }
}
