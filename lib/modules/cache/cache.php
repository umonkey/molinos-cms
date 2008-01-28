<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

interface iBebopCacheEngine
{
    static public function getInstance();
    static public function isAvailable();
    public function getPrefix();
    public function flush($now = false);
}

require_once(dirname(__FILE__).'/cache-apc.inc');
require_once(dirname(__FILE__).'/cache-db.inc');
require_once(dirname(__FILE__).'/cache-file.inc');
require_once(dirname(__FILE__).'/cache-memcache.inc');

class BebopCache
{
    private static $type = null;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$type === null)
            self::chooseEngine();

        return call_user_func(array(self::$type, 'getInstance'));
    }

    private static function chooseEngine()
    {
        $engines = array('MemCacheD_Cache', 'APC_Cache', 'Local_Cache');

        foreach ($engines as $engine) {
            if (call_user_func(array($engine, 'isAvailable'))) {
                self::$type = $engine;
                break;
            }
        }
    }

    public function getPrefix()
    {
        return 'n/a';
    }
}
