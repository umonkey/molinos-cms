<?php

class AdminExtReqHook implements iRequestHook
{
  public static function hookRequest(Context $ctx = null)
  {
    $conf = mcms::modconf('adminext');

    if (empty($conf['groups']))
      return;

    if (!count(array_intersect($conf['groups'], array_keys($ctx->user->getGroups()))))
      return;

    mcms::extras('themes/all/jquery/jquery.js');
    mcms::extras('lib/modules/adminext/adminext.js');
    mcms::extras('lib/modules/adminext/adminext.css');
  }
}
