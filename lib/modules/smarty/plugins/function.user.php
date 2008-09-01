<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

function smarty_function_user($params, &$smarty)
{
  $node = mcms::user();

  if (!array_key_exists('field', $params))
    $result = $node->getRaw();
  else
    $result = $node->$params['field'];

  if (array_key_exists('assign', $params))
    $smarty->assign($params['assign'], $result);
  else
    return $result;
}
