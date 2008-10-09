<?php

function smarty_function_duration($params, &$smarty)
{
  $value = empty($params['value']) ? 0 : $params['value'];
  $mul = empty($params['mul']) ? 1 : $params['mul'];

  $value *= $mul;

  $result = sprintf('%d:%02d:%02d',
    $value / 216000,
    ($value / 3600) % 3600,
    ($value / 60) % 60);

  if ($value < 216000)
    $result = substr($result, 2);

  if (empty($params['assing']))
    return $result;
  else
    $smarty->assign($params['assign'], $result);
}
