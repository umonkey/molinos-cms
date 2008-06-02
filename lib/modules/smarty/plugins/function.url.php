<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

function smarty_function_url($params, &$smarty) 
{
  if (empty($params['path']) or empty($params['text']))
    throw new SmartyException(t("Отсутствует URL и/или текст ссылки."));

  $a = array();
  $url = bebop_split_url($params['path']);

  $a['href'] = bebop_combine_url($url);
  $a['title'] = $params['title'];
  $a['style'] = $params['style'];
  $a['target'] = $params['target'];
 
  return mcms::html('a', $a, $params['text']);
}
