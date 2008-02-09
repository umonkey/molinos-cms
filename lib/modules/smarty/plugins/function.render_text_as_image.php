<?php

function smarty_function_render_text_as_image($params, &$smarty)
{
  $url = array(
    'path' => '/',
    'args' => array(
      'widget' => 'BebopDrawText',
      'BebopDrawText' => array(
        'text' => base64_encode($params['text']),
        'font' => $params['font'],
        'color' => empty($params['color']) ? '0' : $params['color'],
        'size' => empty($params['size']) ? 20 : $params['size'],
        ),
      ),
    );

  $link = bebop_combine_url($url);
  return $link;
}
