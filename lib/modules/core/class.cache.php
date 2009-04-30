<?php
// Работа с оперативным кэшем для Molinos CMS.
// Для выбора конкретного кэша в начало index.php нужно добавить:
// define('MCMS_CACHE_PROVIDER', 'имя_класса');

interface iCacheProvider
{
  public function getName();

  public function __get($key);
  public function __set($key, $value);
  public function __isset($key);
  public function __unset($key);
}

abstract class cache implements iCacheProvider
{
  private static $instance = null;

  private $_flush = false;

  protected $ttl = null;
  protected $prefix = null;

  protected function __construct()
  {
    $this->ttl = 3600;
    $this->setPrefix();
  }

  public static function getInstance()
  {
    if (self::$instance === null) {
      $list = array('XCache_provider', 'MemCache_provider', 'APC_provider', 'DBA_DB4_provider', 'DBA_FlatFile_provider', 'FileCache_provider');

      if (defined('MCMS_CACHE_PROVIDER') and class_exists(MCMS_CACHE_PROVIDER))
        array_unshift($list, MCMS_CACHE_PROVIDER);

      foreach ($list as $class)
        if (null !== (self::$instance = call_user_func(array($class, 'initialize'))))
          break;
    }

    return self::$instance;
  }

  public function flush($now = false)
  {
    if (!$now)
      $this->_flush = true;

    elseif ($this->_flush) {
      // mcms::flog('flush');
      $this->setPrefix(true);
      $this->_flush = false;
    }
  }

  private function setPrefix($increment = false)
  {
    $this->prefix = null;

    $serial = intval($this->__serial);

    if ($increment)
      $this->__serial = ++$serial;

    $this->prefix = 'mcms:' . crc32(__FILE__) . ':' . $serial . ':';

    // mcms::flog('prefix: ' . $this->prefix);
  }

  public static function set($key, $value)
  {
    self::getInstance()->$key = $value;
  }

  public static function get($key, $default = false)
  {
    if (!($value = self::getInstance()->$key))
      $value = $default;
    return $value;
  }
}

class XCache_provider extends cache
{
  public static function initialize()
  {
    if (!function_exists('xcache_set'))
      return null;
    if (!intval(ini_get('xcache.var_size')))
      return null;
    return new XCache_provider();
  }

  public function getName()
  {
    return 'XCache';
  }

  public function __get($key)
  {
    return unserialize(xcache_get($this->prefix . $key));
  }

  public function __set($key, $value)
  {
    return xcache_set($this->prefix . $key, serialize($value));
  }

  public function __isset($key)
  {
    return xcache_isset($this->prefix . $key);
  }

  public function __unset($key)
  {
    return xcache_unset($this->prefix . $key);
  }
}

class APC_provider extends cache
{
  public static function initialize()
  {
    return function_exists('apc_store')
      ? new APC_provider()
      : null;
  }

  public function getName()
  {
    return 'APC';
  }

  public function __get($key)
  {
    return apc_fetch($this->prefix . $key);
  }

  public function __set($key, $value)
  {
    return apc_store($this->prefix . $key, $value, $this->ttl);
  }

  public function __isset($key)
  {
    return apc_fetch($this->prefix . $key) !== false;
  }

  public function __unset($key)
  {
    return apc_delete($this->prefix . $key);
  }
}

class MemCache_provider extends cache
{
  private $host;
  private $flags = MEMCACHE_COMPRESSED;

  public static function initialize()
  {
    return class_exists('Memcache', false)
      ? new MemCache_provider()
      : null;
  }

  public function getName()
  {
    return 'MemCache';
  }

  public function __construct()
  {
    $host = mcms::config('cache.memcache.host', 'localhost');
    $this->host = new Memcache();
    $this->host->pconnect($host);

    parent::__construct();
  }

  public function __get($key)
  {
    return $this->host->get($this->prefix . $key);
  }

  public function __set($key, $value)
  {
    return $this->host->set($this->prefix . $key, $value, $this->flags, $this->ttl);
  }

  public function __isset($key)
  {
    return $this->host->get($this->prefix . $key) !== false;
  }

  public function __unset($key)
  {
    return $this->host->delete($this->prefix . $key);
  }
}

class DBA_DB4_provider extends cache
{
  protected $db;
  protected $write = false;
  private $handler;

  public function __construct($handler)
  {
    $this->handler = $handler;
    if (file_exists($filename = $this->getFileName()))
      $this->db = dba_open($filename, 'rd', $this->handler);
  }

  protected function getFileName()
  {
    return MCMS_SITE_FOLDER . DIRECTORY_SEPARATOR . 'cache.' . $this->handler;
  }

  public static function initialize()
  {
    if (in_array('db4', dba_handlers()))
      return new DBA_DB4_provider('db4');
  }

  public function getName()
  {
    return 'DBA/' . $this->handler;
  }

  public function __get($key)
  {
    if (!$this->db)
      return false;
    if (false !== ($value = dba_fetch($key, $this->db)))
      $value = unserialize($value);
    return $value;
  }

  /**
   * Открывает БД для записи, если она открыта для чтения.
   */
  protected function reopen()
  {
    if ($this->write)
      return;
    if ($this->db)
      dba_close($this->db);
    $this->db = dba_open($this->getFilename(), 'cd', $this->handler);
  }

  public function __set($key, $value)
  {
    $this->reopen();
    dba_replace($key, serialize($value), $this->db);
  }

  public function __isset($key)
  {
    if (false === $this->db)
      return false;
    return dba_exists($key, $this->db);
  }

  public function __unset($key)
  {
    $this->reopen();
    dba_delete($key, $this->db);
  }
}

class DBA_FlatFile_provider extends DBA_DB4_provider
{
  public static function initialize()
  {
    if (in_array('flatfile', dba_handlers()))
      return new DBA_FlatFile_provider('flatfile');
  }
}

class FileCache_provider extends cache
{
  private static $path = null;

  public static function initialize()
  {
    self::$path = MCMS_ROOT . DIRECTORY_SEPARATOR . MCMS_SITE_FOLDER . DIRECTORY_SEPARATOR . 'tmp';
    return new FileCache_provider();
  }

  public function getName()
  {
    return 'FileCache';
  }

  public function __get($key)
  {
    if (!$this->__isset($key))
      return false;
    return unserialize(file_get_contents($this->getKeyPath($key)));
  }

  public function __set($key, $value)
  {
    file_put_contents($this->getKeyPath($key), serialize($value));
  }

  public function __isset($key)
  {
    return file_exists($this->getKeyPath($key));
  }

  public function __unset($key)
  {
    if ($this->__isset($key))
      unlink($this->getKeyPath($key));
  }

  public function flush($now = false)
  {
    if ($now) {
      if (is_array($files = glob(self::$path . DIRECTORY_SEPARATOR . '*')))
        foreach ($files as $file)
          if (is_file($file))
            unlink($file);
    }
    return parent::flush($now);
  }

  private function getKeyPath($key)
  {
    return self::$path . DIRECTORY_SEPARATOR . md5($this->prefix . $key);
  }
}
