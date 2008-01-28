<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

require_once("Smarty.class.php");

class SmartyException extends LogicException {}

class BebopSmarty extends Smarty
{
    public function __construct($with_debug = false)
    {
        $config = BebopConfig::getInstance();

        $this->Smarty();

        if (is_dir($config->smarty_plugins_dir)) {
          $plugins = $this->plugins_dir;
          $plugins[] = $config->smarty_plugins_dir;
          $this->plugins_dir = $plugins;
        }

        // $this->template_dir = $config->smarty_template_dir;
        $this->compile_dir = $config->smarty_compile_dir;
        // $this->config_dir = $config->smarty_config_dir;
        $this->cache_dir = $config->smarty_cache_dir;

        $this->caching = false;
        if (isset($config->smarty_cache_lifetime))
            $this->cache_lifetime = $config->smarty_cache_lifetime;

        if ($with_debug and !empty($_GET['smarty_debug']) and (bebop_is_debugger() or in_array('Developers', (array)@$_SESSION['user']['groups'])))
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
}
