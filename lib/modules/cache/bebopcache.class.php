<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

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

        if (self::$type !== null)
            return call_user_func(array(self::$type, 'getInstance'));
    }

    private static function chooseEngine()
    {
        $engines = array('MemCacheD_Cache', 'APC_Cache', 'Local_Cache');

        foreach ($engines as $engine) {
            if (mcms::class_exists($engine) and call_user_func(array($engine, 'isAvailable'))) {
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
