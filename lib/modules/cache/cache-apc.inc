<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

class APC_Cache implements iBebopCacheEngine
{
    private static $instance = null;
    private $apc_available;
    private $prefix = null;
    private $ttl = 3600;
    private $flush = false;

    private function __construct()
    {
        $this->apc_available = function_exists('apc_store');
        $this->prefix = 'bbp_'.hash('crc32', __FILE__).'_'; //making prefix, which is unique to this project
    }

    static public function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new APC_Cache();
        }

        return self::$instance;
    }

    static public function isAvailable()
    {
        if (in_array('apc', preg_split('/, */', mcms::config('cache.disable'))))
            return false;

        return function_exists('apc_store');
    }

    private function __get($varname)
    {
        return apc_fetch($this->prefix.$varname);
    }

    private function __isset($varname)
    {
        return (apc_fetch($this->prefix.$varname) === false);
    }

    private function __set($varname, $value)
    {
        apc_store($this->prefix.$varname, $value, $this->ttl);
    }

    private function __unset($varname)
    {
        apc_delete($this->prefix.$varname);
    }

    public function count()
    {
        $stats = apc_cache_info('user');
        return count($stats['cache_list']);
    }

    public function flush($now = false)
    {
        if ($now and $this->flush) {
            DBCache::getInstance()->flush();
            apc_clear_cache('user');
        } else {
            $this->flush = true;
        }
    }

    public function getPrefix()
    {
        return $this->prefix;
    }
}
