<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

function smarty_function_get_random($params, &$smarty)
{
  $result = null;

  if (!empty($params['items'])) {
      $key = array_rand($params['items'], 1);
      $result = $params['items'][$key];
  }

  $smarty->assign($params['assign'], $result);
}
