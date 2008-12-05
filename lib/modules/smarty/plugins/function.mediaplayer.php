<?php

function smarty_function_mediaplayer($params, &$smarty)
{
  if (empty($params['doc']['class']))
    throw new SmartyException(t('Параметр doc функции {mediaplayer} должен содержать массив с описанием документа.'));

  $files = empty($params['doc']['files'])
    ? array()
    : $params['doc']['files'];

  foreach ($params['doc'] as $k => $v)
    if (is_array($v) and array_key_exists('class', $v) and 'file' == $v['class']) {
      $files[] = $v;
    }

  if (empty($files))
    return;

  return mcms::mediaGetPlayer($files);
}
