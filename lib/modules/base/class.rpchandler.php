<?php

class RPCHandler
{
  public static function hookRemoteCall(Context $ctx, $className)
  {
    $default = 'default';

    if ($ctx->method('post'))
      $default = $ctx->post('action', $default);

    $action = $ctx->get('action', $default);

    $call = array(
      array($className, 'rpc_' . strtolower($ctx->method()) . '_' . $action),
      array($className, 'rpc_' . $action),
      );

    foreach ($call as $args) {
      if (method_exists($args[0], $args[1])) {
        if (null === ($result = call_user_func(array($args[0], $args[1]), $ctx)))
          $result = $ctx->getRedirect();
        return $result;
      }
    }

    mcms::debug($call);

    return false;
  }
}
