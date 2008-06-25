<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

function smarty_function_add_extras($params, &$smarty)
{
  if (empty($params['file']))
    throw new SmartyException(t('Не указан параметр file.'));

  mcms::extras($params['file'], empty($params['standalone']));
}
