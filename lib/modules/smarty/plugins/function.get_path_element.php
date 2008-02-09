<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

function smarty_function_get_path_element($params, &$smarty)
{
  static $path = null;
  
  if ($path === null)
    $path = explode('/', preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']));

  if (!empty($params['index']) and is_numeric($params['index']))
    $rc = @$path[$params['index']];
  else
    $rc = "";

  $smarty->assign($params['assign'], $rc);
}
