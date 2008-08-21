<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

class Local_Cache implements iBebopCacheEngine
{
    private static $instance = null;
    private $storage = null;
    private $flush = false;

    private function __construct()
    {
        $this->storage = array();
    }

    static public function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Local_Cache();
        }

        return self::$instance;
    }

    static public function isAvailable()
    {
        return true;
    }

    private function __get($varname)
    {
        if (!array_key_exists($varname, $this->storage))
            return false;

        return $this->storage[$varname];
    }

    private function __isset($varname)
    {
        return array_key_exists($varname, $this->storage);
    }

    private function __set($varname, $value)
    {
        $this->storage[$varname] = $value;
    }

    private function __unset($varname)
    {
        unset($this->storage[$varname]);
    }

    public function count()
    {
        return count($storage);
    }

    public function flush($now = false)
    {
        if ($now and $this->flush) {
            DBCache::getInstance()->flush($now);
            $storage = array();
        } else {
            $this->flush = true;
        }
    }

    public function getPrefix()
    {
        return 'local_';
    }
}
