<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

class DBCache implements iBebopCacheEngine
{
    private $pdo = null;
    private $lang = null;

    private static $instance = null;

    public function __construct($lang = null)
    {
        $this->lang = $lang;
        $this->pdo = mcms::db();

        if (self::$instance === null)
            self::$instance = $this;
    }

    static public function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new DBCache();
        }

        return self::$instance;
    }

    static public function isAvailable()
    {
        return true;
    }

    private function __get($key)
    {
        try {
            $value = $this->pdo->getResult("SELECT `data` FROM `node__cache` WHERE `cid` = :cid AND `lang` = :lang",
                array(':cid' => $key, ':lang' => $this->lang));

            if (!empty($value))
                return @unserialize($value);
        } catch (PDOException $e) {
            // Base table or view not found.
            if ($e->getCode() == '42S02')
                throw new NotInstalledException('table');
            else
                throw $e;
        }

        return null;
    }

    private function __isset($varname)
    {
        return $this->__get($varname) !== null;
    }

    private function __set($key, $value)
    {
        try {
            $this->pdo->exec("REPLACE INTO `node__cache` (`cid`, `lang`, `data`) VALUES (:cid, :lang, :data)",
                array(':cid' => $key, ':lang' => empty($this->lang) ? 'en' : $this->lang, ':data' => serialize($value)));
        } catch (PDOException $e) {
            if ($e->getCode() == '42S02')
                throw new NotInstalledException('table');
            else
                throw $e;
        }
    }

    private function __unset($varname)
    {
        $this->__set($varname, null);
    }

    public function count()
    {
        return $this->pdo->getResult("SELECT COUNT(*) FROM `node__cache`");
    }

    public function flush($now = true)
    {
        if ($now) {
            try {
                $this->pdo->exec("DELETE FROM `node__cache`");
            } catch (PDOException $e) {
            }
        }
    }

    public function getPrefix()
    {
        return null;
    }
}
