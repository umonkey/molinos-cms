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
          'exists' => function_exists('xcache_set'),
          ),
        'memcache' => array(
          'class' => 'MemCache_provider',
          'exists' => class_exists('Memcache', false),
          ),
        'apc' => array(
          'class' => 'APC_provider',
          'exists' => function_exists('apc_store'),
          ),
        'local' => array(
          'class' => 'FileCache_provider',
          'exists' => true,
          ),
        );

      $config = mcms::config('cache', array());

      foreach ($map as $k => $v) {
        if (array_key_exists($k, $config) and empty($config[$k]))
          continue;

        if ($v['exists']) {
          mcms::flog('cache', 'using ' . str_replace('_provider', '', $v['class']));
          self::$instance = new $v['class']();
          break;
        }
      }
    }

    return self::$instance;
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
      mcms::flog('cache', 'flush');
      $this->setPrefix(true);
      $this->_flush = false;
    }
  }

  private function setPrefix($increment = false)
  {
    $this->prefix = null;
    $serial = intval($this->__serial)
      + ($increment ? 1 : 0);
    $this->prefix = 'mcms:' . crc32(__FILE__) . ':' . $serial . ':';
  }
}

class XCache_provider extends BebopCache
{
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

class APC_provider extends BebopCache
{
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

class MemCache_provider extends BebopCache
{
  private $host;
  private $flags = MEMCACHE_COMPRESSED;

  public function __construct()
  {
    $host = mcms::config('cache_memcache_host', 'localhost');
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

class FileCache_provider extends BebopCache
{
  private $storage = array();

  public function __get($key)
  {
    if (!self::__isset($key))
      return false;
    return $this->storage[$this->prefix . $key];
  }

  public function __set($key, $value)
  {
    $this->storage[$this->prefix . $key] = $value;
  }

  public function __isset($key)
  {
    return array_key_exists($this->prefix . $key, $this->storage);
  }

  public function __unset($key)
  {
    unset($this->storage[$this->prefix . $key]);
  }

  public function flush($now = false)
  {
    if ($now)
      $this->storage = array();
    return parent::flush($now);
  }
}
