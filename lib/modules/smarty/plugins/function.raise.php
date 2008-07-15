<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

function smarty_function_raise($params, &$smarty)
{
  if (empty($params['type']))
    throw new SmartyException(t('Не указан тип исключения (параметр type)'));
  elseif (!class_exists($params['type']))
    throw new SmartyException(t('Неизвестный тип исключения: %type',
      array('%type' => $params['type'])));

  if (empty($params['message']))
    throw new $params['type']();
  else
    throw new $params['type']($params['message']);
}
