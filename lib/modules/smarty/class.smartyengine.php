<?php

class SmartyEngine implements iTemplateProcessor
{
  public static function getExtensions()
  {
    return array('tpl');
  }

  public static function processTemplate($fileName, array $data)
  {
    if (!class_exists('Smarty'))
      require os::path(dirname(__FILE__), 'Smarty-2.6.21', 'libs', 'Smarty.class.php');

    $__smarty = new BebopSmarty();

    $__smarty->template_dir = ($__dir = dirname($fileName));

    if (is_dir($__dir . DIRECTORY_SEPARATOR . 'plugins')) {
      $__plugins = $__smarty->plugins_dir;
      $__plugins[] = $__dir . DIRECTORY_SEPARATOR . 'plugins';
      $__smarty->plugins_dir = $__plugins;
    }

    // Двойной вызов dirname() нужен потому, что шаблон лежит
    // в папке templates, а нам нужен её родитель.
    if (empty($data['prefix']))
      $data['prefix'] = os::localpath(dirname(dirname($fileName)));

    foreach ($data as $k => $v)
      $__smarty->assign($k, $v);

    error_reporting(($old = error_reporting()) & ~E_NOTICE);

    $compile_id = md5($fileName);

    ob_start();
    $__smarty->display(basename($fileName), $compile_id, $compile_id);
    $output = ob_get_clean();

    error_reporting($old);

    return $output;
  }
}
