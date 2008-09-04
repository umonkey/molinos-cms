<?php

function smarty_function_render_text_as_image($params, &$smarty)
{
  $url = new url('?q=drawtext.rpc');

  $url->setarg('text', base64_encode($params['text']));
  $url->setarg('font', $params['font']);
  $url->setarg('color', empty($params['color']) ? '000000' : $params['color']);
  $url->setarg('bgcolor', empty($params['bgcolor']) ? 'ffffff' : $params['bgcolor']);
  $url->setarg('size', empty($params['size']) ? 20 : $params['size']);

  return strval($url);
}
