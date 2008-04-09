<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class BaseModule implements iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    $next = $ctx->get('destination', '/');

    switch ($ctx->get('action')) {
    case 'login':
      try {
        mcms::user()->authorize($_POST['login'], $_POST['password']);
      } catch (ObjectNotFoundException $e) {
        if (null !== ($tmp = $ctx->get('onerror')))
          $next = $tmp;
      }
      break;
    case 'logout':
      mcms::user()->authorize();
      break;
    }

    bebop_redirect($next);
  }
};
