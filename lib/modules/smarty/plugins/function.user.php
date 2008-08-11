<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

function smarty_function_user($params, &$smarty)
{
  $node = mcms::user();

  if (!array_key_exists('field', $params))
    return $node->getRaw();
  elseif (array_key_exists('assign', $params))
    $smarty->assign($params['assign'], $node->$params['field']);
  else
    return $node->$params['field'];
}
