<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class BaseModule implements iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    switch ($ctx->get('action')) {
    case 'login':
      mcms::user()->authorize($_POST['login'], $_POST['password']);
      break;
    case 'logout':
      mcms::user()->authorize();
      break;
    }

    $next = $ctx->get('destination', '/');

    bebop_redirect($next);
  }
};
