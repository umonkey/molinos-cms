<?php

function smarty_function_mediaplayer($params, &$smarty)
{
  if (empty($params['doc']['class']))
    throw new SmartyException(t('Параметр doc функции {mediaplayer} должен содержать массив с описанием документа.'));

  if (empty($params['doc']['files']))
    return;

  print mcms::mediaGetPlayer($params['doc']['files']);
}
