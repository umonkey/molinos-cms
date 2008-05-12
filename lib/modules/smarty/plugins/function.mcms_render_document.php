<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

function smarty_function_mcms_render_document($params, &$smarty)
{
  $class = null;

  foreach ($params as $p)
    if (is_array($p) and array_key_exists('class', $p))
      $class = $p['class'];

  if (null !== $class) {
    $args = $params;

    $html = bebop_render_object('type', $class, null, $args);

    if (isset($params['assign']))
      $smarty->assign($params['assign'], $html);
    else
      return $html;
  }
}
