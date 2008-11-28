<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

function smarty_function_get_path_element($params, &$smarty)
{
  if (!array_key_exists('index', $params))
    throw new SmartyException(t('{get_path_element} предполагает наличие параметра index.'));

  if (null === ($path = Context::last()->query()))
    $path = array();
  else
    $path = preg_split('@/@', $path, -1, PREG_SPLIT_NO_EMPTY);

  $result = array_key_exists($idx = intval($params['index']) - 1, $path)
    ? $path[$idx]
    : '';

  if (array_key_exists('assign', $params))
    $smarty->assign($params['assign'], $result);
  else
    return $result;
}
