<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

function smarty_function_mcms_render_document($params, &$smarty)
{
  if (empty($params['doc']) or !is_array($params['doc']))
    return null;

  $html = bebop_render_object('type', $params['doc']['class'], null, array('document' => $params['doc']));

  if (isset($params['assign']))
    $smarty->assign($params['assign'], $html);
  else
    return $html;
}
