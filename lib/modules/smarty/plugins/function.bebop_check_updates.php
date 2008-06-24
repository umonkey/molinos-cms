<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

function smarty_function_bebop_check_updates($params, &$smarty)
{
  $output = '';

  $um = new UpdateManager();
  $ver = $um->getVersionInfo();

  if ($ver['current_build'] < $ver['latest_build']) {
    $output = t("<a href='@link' title='%version.%current &rarr; %version.%latest' id='lnk_update'>Обновление</a>", array(
      '@link' => 'adminupdate/',
      '%version' => $ver['release'],
      '%current' => $ver['current_build'],
      '%latest' => $ver['latest_build'],
      ));
  }

  return $output;
}
