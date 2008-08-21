<?php
/**
 * Оперативное кэширование данных в APC.
 *
 * @package mod_cache
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Оперативное кэширование данных в APC.
 *
 * @package mod_cache
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */
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

    /**
     * Получение объекта для работы с кэшем.
     *
     * @return APC_Cache интерфейс к кэшу.
     */
    static public function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new APC_Cache();
        }

        return self::$instance;
    }

    /**
     * Проверка наличия APC.
     *
     * @return bool true, если APC есть.
     */
    static public function isAvailable()
    {
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

    /**
     * Определение размера кэша.
     *
     * @return integer количество объектов в кэше.
     */
    public function count()
    {
        $stats = apc_cache_info('user');
        return count($stats['cache_list']);
    }

    /**
     * Очистка кэша.
     *
     * @param bool $now true если кэш нужно очистить немедленно, false если
     * можно потом.
     */
    public function flush($now = false)
    {
        if ($now and $this->flush) {
            DBCache::getInstance()->flush();
            apc_clear_cache('user');
        } else {
            $this->flush = true;
        }
    }

    /**
     * Получение префикса.
     *
     * Префикс используется для обеспечения уникальности в именах кэшируемых
     * объектов (наложение возникает когда несколько сайтов используют общий
     * кэш).
     *
     * @return string префикс для переменных.
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}
