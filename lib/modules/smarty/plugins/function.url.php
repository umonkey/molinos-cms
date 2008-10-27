<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

function smarty_function_url($params, &$smarty) 
{
  if (!empty($params['link'])) {
    $tmp = new url($params['link']);
    return $tmp;
  } elseif (empty($params['path']) or empty($params['text'])) {
    throw new SmartyException(t("Отсутствует URL и/или текст ссылки."));
  } else {
    $a = array();
    $url = new url($params['path']);

    $a['href'] = $url->string();
    $a['title'] = $params['title'];
    $a['style'] = $params['style'];
    $a['target'] = $params['target'];
   
    return mcms::html('a', $a, $params['text']);
  }
}
