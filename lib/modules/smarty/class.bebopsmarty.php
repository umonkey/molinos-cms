<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

class BebopSmarty extends Smarty
{
    public function __construct($with_debug = false)
    {
        require_once os::path(dirname(__FILE__), 'Smarty-2.6.21', 'libs', 'Smarty.class.php');

        $this->Smarty();

        if (is_dir($tmp = os::path(dirname(__FILE__), 'plugins'))) {
          $plugins = $this->plugins_dir;
          $plugins[] = $tmp;
          $this->plugins_dir = $plugins;
        }

        $tmpdir = mcms::config('tmpdir');

        $this->compile_dir = mcms::mkdir(os::path($tmpdir, 'smarty_compile_dir'), t('Не удалось создать папку для компиляции шаблонов (%path)', array('%path' => $this->compile_dir)));
        $this->cache_dir = mcms::mkdir(os::path($tmpdir, 'smarty_cache'), t('Не удалось создать папку для кэширования шаблонов (%path)', array('%path' => $this->cache_dir)));

        $this->caching = false;

        if (null !== ($tmp = mcms::config('smarty.cache.lifetime')))
            $this->cache_lifetime = $tmp;

        /*
        if ($with_debug and self::debug())
            $this->debugging = true;
        else
            $this->debugging = false;
        */
    }

    public function trigger_error($error_msg, $error_type = E_USER_WARNING)
    {
        throw new SmartyException($error_msg, $error_type);
    }
}
