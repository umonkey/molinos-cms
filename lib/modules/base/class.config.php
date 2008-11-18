<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Config
{
  private static $instance = null;
  private $data = null;
  private $path = null;
  private $isok = false;

  private function __construct()
  {
    $this->path = $this->findFile();
  }

  static public function getInstance()
  {
    if (self::$instance === null)
      self::$instance = new Config();
    return self::$instance;
  }

  private function readData()
  {
    if (!is_readable($this->path)) {
      $this->data = array();
    }

    elseif (substr($this->path, -4) == '.ini') {
      $this->data = array();

      foreach (parse_ini_file($this->path, true) as $k => $v) {
        if (!is_array($v))
          $this->data[str_replace('_', '.', $k)] = $v;
        else foreach ($v as $sk => $sv) {
          $this->data[str_replace('_', '.', $k . '.' . $sk)] = $sv;
        }
      }

      // Заменяем "off", который парсится в "", на bool.
      foreach ($this->data as $k => $v)
        if ('' === $v)
          $this->data[$k] = false;

      // Разворачиваем известные массивы.
      foreach (array('backtracerecipients', 'runtime.modules') as $k)
        if (array_key_exists($k, $this->data))
          $this->data[$k] = preg_split('/,\s*/', $this->data[$k], -1, PREG_SPLIT_NO_EMPTY);
        else
          $this->data[$k] = array();

      $this->isok = true;
    }

    else {
      $this->data = require_once $this->path;
      $this->isok = true;
    }

    $this->data['cleanurls'] = !empty($_GET['__cleanurls']);
  }

  private function findFile()
  {
    $options = array();

    for ($parts = array_reverse(explode('.', $_SERVER['HTTP_HOST'])); !empty($parts); array_pop($parts)) {
      $path = 'conf' . DIRECTORY_SEPARATOR . join('.', $parts);
      $options[] = $path . '.config.php';
      $options[] = $path . '.ini';
    }

    $options[] = 'conf' . DIRECTORY_SEPARATOR . 'default.config.php';
    $options[] = 'conf' . DIRECTORY_SEPARATOR . 'default.ini';

    foreach ($options as $path)
      if (file_exists($path) and is_readable($path))
        return $path;

    // Ничего не найдено, пытаемся использовать фабричный конфиг.
    if (file_exists($src = 'conf' . DIRECTORY_SEPARATOR . 'default.config.php.dist'))
      if (copy($src, $dst = 'conf' . DIRECTORY_SEPARATOR . 'default.config.php'))
        return $dst;

    throw new RuntimeException(t('Не удалось найти конфигурационный файл.'));
  }

  private function __isset($varname)
  {
    return null !== $this->__get($varname);
  }

  private function __get($varname)
  {
    if (null === $this->data)
      $this->readData();

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
    case 'fullpath':
      $res = $this->path;
      break;
    default:
      $res = array_key_exists($varname, $this->data)
        ? $this->data[$varname]
        : null;
    }

    if (null === $res)
      mcms::flog('config', $varname . ': not found.');

    return $res;
  }

  private function __set($varname, $value)
  {
    if (empty($varname))
      throw new InvalidArgumentException(t('Не указано имя параметра.'));

    if (null === $this->data)
      $this->readData();

    $this->data[$varname] = $value;
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
    return $this->isok;
  }

  public function reload()
  {
    if (empty($this->data))
      $this->readData();
  }

  public function set($varname, $value, $section = null)
  {
    if (null !== $section)
      $varname = $section . '.' . $varname;
    return $this->__set($varname, $value);
  }

  public function write()
  {
    if (strrchr($this->path, '.') == '.ini')
      $this->path = substr($this->path, 0, -4) . '.config.php';

    // Запись в новый файл.
    $content = "<?php return " . var_export($this->data, true) . ";\n";
    mcms::writeFile($this->path, $content);

    // Удаление старых файлов.
    if (file_exists($old = substr($this->path, 0, -11) . '.ini')) {
      mcms::flog('config', $old . ': removing (deprecated).');
      unlink($old);
    }
  }

  public function isWritable()
  {
    return is_writable($this->path);
  }
}
