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
        if (self::$type === null) {
            self::chooseEngine();

            if (null !== self::$type) {
                if (bebop_is_debugger() and !empty($_GET['flush'])) {
                    $f = array(self::$type, 'flush');
                    call_user_func($f, false);
                    call_user_func($f, true);
                }
            }
        }

        if (self::$type !== null)
            return call_user_func(array(self::$type, 'getInstance'));
    }

    private static function chooseEngine()
    {
        $engines = array('memcache' => 'MemCacheD_Cache', 'apc' => 'APC_Cache', 'local' => 'Local_Cache');
        $disabled = preg_split('/, */', mcms::config('cache_disable'), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($engines as $key => $engine) {
            if (!in_array($key, $disabled) and mcms::class_exists($engine) and call_user_func(array($engine, 'isAvailable'))) {
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
