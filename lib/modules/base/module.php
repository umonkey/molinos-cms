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
    default:
      throw new PageNotFoundException();
    }

    bebop_redirect($ctx->get('destination', '/'));
  }
}
