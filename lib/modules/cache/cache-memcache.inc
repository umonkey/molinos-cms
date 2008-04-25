<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

class MemCacheD_Cache implements iBebopCacheEngine
{
    private static $instance = null;
    private $cache = null;
    private $prefix = null;
    private $ttl = 3600;
    private $flags = MEMCACHE_COMPRESSED;
    private $flush = false;

    private function __construct()
    {
        $delta = (empty($_GET['flush']) and !empty($_SERVER['REMOTE_ADDR'])) ? 0 : 1;

        $this->cache = new Memcache();
        $this->cache->pconnect('localhost');
        $this->setPrefix($delta);
    }

    private function setPrefix($delta = 0)
    {
        // Базовый префикс для работы с кэшем.
        $this->prefix = 'bbp:'.hash('crc32', __FILE__).':';

        // Вычисляем (и обновляем) серийный номер.
        $serial = $this->memcached_serial;
        if (empty($serial))
            $serial = 1;
        $serial += $delta;
        $this->memcached_serial = $serial;

        // Добавляем серийный номер к префиксу.
        $this->prefix .= $serial .':';
    }

    static public function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new MemCacheD_Cache();
        }

        return self::$instance;
    }

    static public function isAvailable()
    {
        if (in_array('memcache', preg_split('/, */', mcms::config('cache.disable'))))
            return false;

        return class_exists('Memcache', false);
    }

    private function __get($varname)
    {
        return $this->cache->get($this->prefix.$varname);
    }

    private function __isset($varname)
    {
        return ($this->cache->get($this->prefix.$varname) === false);
    }

    private function __set($varname, $value)
    {
        $this->cache->set($this->prefix.$varname, $value, $this->flags, $this->ttl);
    }

    private function __unset($varname)
    {
        $this->cache->delete($this->prefix.$varname);
    }

    public function count()
    {
        $stats = $this->cache->getStats();
        return $stats['curr_items'];
    }

    public function flush($now = false)
    {
        if ($now and $this->flush) {
            $this->setPrefix(1);
            DBCache::getInstance()->flush();
        } else {
            $this->flush = true;
        }
    }

    public function getPrefix()
    {
        return $this->prefix;
    }
}
