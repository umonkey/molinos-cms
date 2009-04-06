<?php

class BebopCache
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
      $map = array(
        'xcache' => array(
          'class' => 'XCache_provider',
          ),
        'memcache' => array(
          'class' => 'MemCache_provider',
          ),
        'apc' => array(
          'class' => 'APC_provider',
          ),
        'local' => array(
          'class' => 'FileCache_provider',
          ),
        );

      $disabled = mcms::config('cache.disable', array());

      foreach ($map as $k => $v) {
        if (in_array($k, $disabled))
          continue;

        if (call_user_func(array($v['class'], 'isAvailable'))) {
          // mcms::flog('using ' . str_replace('_provider', '', $v['class']));
          self::$instance = new $v['class']();
          break;
        }
      }

      if (null === self::$instance)
        throw new RuntimeException(t('Недоступен ни один провайдер кэша. Проверьте права на файловую систему (tmp/cache).'));
    }

    return self::$instance;
  }

  public function getName()
  {
    return get_class($this);
  }

  public function __get($key)
  {
    throw new RuntimeException(get_class($this) . '::__get() not implemented.');
  }

  public function __set($key, $value)
  {
    throw new RuntimeException(get_class($this) . '::__set() not implemented.');
  }

  public function __isset($key)
  {
    throw new RuntimeException(get_class($this) . '::__isset() not implemented.');
  }

  public function __unset($key)
  {
    throw new RuntimeException(get_class($this) . '::__unset() not implemented.');
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
}

class XCache_provider extends BebopCache
{
  public static function isAvailable()
  {
    if (!function_exists('xcache_set'))
      return false;
    if (!intval(ini_get('xcache.var_size')))
      return false;
    return true;
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

  public function getName()
  {
    return 'XCache';
  }
}

class APC_provider extends BebopCache
{
  public static function isAvailable()
  {
    return function_exists('apc_store');
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

  public function getName()
  {
    return 'APC';
  }
}

class MemCache_provider extends BebopCache
{
  private $host;
  private $flags = MEMCACHE_COMPRESSED;

  public static function isAvailable()
  {
    return class_exists('Memcache', false);
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

  public function getName()
  {
    return 'MemCache';
  }
}

class FileCache_provider extends BebopCache
{
  private static $path = null;

  public static function isAvailable()
  {
    if (!(self::$path = os::mkdir(Context::last()->config->getPath('tmpdir') . DIRECTORY_SEPARATOR . 'cache')))
      return false;
    return true;
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
          unlink($file);
    }
    return parent::flush($now);
  }

  private function getKeyPath($key)
  {
    return self::$path . DIRECTORY_SEPARATOR . md5($this->prefix . $key);
  }

  public function getName()
  {
    return 'FileCache';
  }
}
