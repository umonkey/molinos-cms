<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

require_once('Smarty.class.php');

class BebopSmarty extends Smarty
{
    public function __construct($with_debug = false)
    {
        $this->Smarty();

        if (is_dir($tmp = dirname(__FILE__) .'/plugins')) {
          $plugins = $this->plugins_dir;
          $plugins[] = $tmp;
          $this->plugins_dir = $plugins;
        }

        $tmpdir = mcms::config('tmpdir');

        $this->compile_dir = mcms::mkdir($tmpdir .'/smarty_compile_dir', t('Не удалось создать папку для компиляции шаблонов (%path)', array('%path' => $this->compile_dir)));
        $this->cache_dir = mcms::mkdir($tmpdir .'/smarty_cache', t('Не удалось создать папку для кэширования шаблонов (%path)', array('%path' => $this->cache_dir)));

        $this->caching = false;

        if (null !== ($tmp = mcms::config('smarty_cache_lifetime')))
            $this->cache_lifetime = $tmp;

        if ($with_debug and self::debug())
            $this->debugging = true;
        else
            $this->debugging = false;
    }

    public function trigger_error($error_msg, $error_type = E_USER_WARNING)
    {
        throw new SmartyException($error_msg, $error_type);
    }

    // ioncube support
    public function _read_file($filename)
    {
        if (!file_exists($filename) or !is_readable($filename)) {
            return false;
        }

        if (function_exists('ioncube_read_file') and ioncube_file_is_encoded()) {
            $res = ioncube_read_file($filename);

            if (is_int($res)) {
                return false;
            }

            return $res;
        } else {
            return file_get_contents($filename);
        }
    }

    public static function debug()
    {
        if (empty($_GET['smarty_debug']))
            return false;
        if (!bebop_is_debugger())
            return false;
        return true;
    }
}
