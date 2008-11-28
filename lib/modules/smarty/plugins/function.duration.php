<?php

function smarty_function_duration($params, &$smarty)
{
  $map = array(
    'value' => 1,
    'seconds' => 1,
    'minutes' => 60,
    'hours' => 3600,
    'days' => 86400,
    );

  $value = null;

  foreach ($map as $k => $v) {
    if (array_key_exists($k, $params)) {
      $value = $params[$k] * $v;
      break;
    }
  }

  if (null === $value)
    throw new RuntimeException(t('{duration} предполагает один из следующих параметров: value, seconds, minutes, hours или days.'));

  $sign = ($value >= 0)
    ? ''
    : '-';

  $value = abs($value);

  $map = array(
    60, ':',
    60, ':',
    // 8, '.',
    );

  $result = '';

  while (!empty($map)) {
    $size = array_shift($map);
    $prefix = array_shift($map);

    $result = sprintf('%s%02d', $prefix, $value % $size) . $result;
    $value = intval($value / $size);
  }

  $result = $sign . $value . $result;

  if (0 === strpos($result, '0.'))
    $result = substr($result, 2);

  if (0 === strpos($result, '00:'))
    $result = substr($result, 3);

  if (empty($params['assing']))
    return $result;
  else
    $smarty->assign($params['assign'], $result);
}
