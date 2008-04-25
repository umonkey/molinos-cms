<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class BaseModule implements iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    switch ($ctx->get('action')) {
    case 'logout':
      AuthCore::getInstance()->userLogOut();
      break;
    case 'login':
      mcms::auth($_POST['login'], $_POST['password']);
      break;
    default:
      throw new PageNotFoundException();
    }

    bebop_redirect($ctx->get('destination', '/'));
  }
}
