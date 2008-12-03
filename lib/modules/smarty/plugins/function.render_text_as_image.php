<?php

function smarty_function_render_text_as_image($params, &$smarty)
{
  $url = new url('?q=drawtext.rpc');

  $url->setarg('text', base64_encode($params['text']));

  foreach (array('font', 'background', 'color', 'bgcolor', 'size', 'x', 'y') as $k)
    if (array_key_exists($k, $params))
      $url->setarg($k, $params[$k]);

  $result = empty($params['noescape'])
    ? htmlspecialchars($url->string())
    : $url->string();

  return $result;
}
