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
        bebop_on_json(array(
          'status' => 'wrong',
          'message' => 'Неверный пароль или имя пользователя.',
          ));

        if (null !== ($tmp = $ctx->get('onerror')))
          $next = $tmp;
      }
      break;
    case 'logout':
      mcms::user()->authorize();
      break;
    }

    bebop_on_json(array('status' => 'ok'));

    mcms::redirect($next);
  }
};
