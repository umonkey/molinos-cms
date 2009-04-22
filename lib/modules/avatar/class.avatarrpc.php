<?php

class AvatarRPC extends RPCHandler
{
  public static function on_rpc(Context $ctx)
  {
    return parent::hookRemoteCall($ctx, __CLASS__);
  }

  protected static function rpc_get_default(Context $ctx)
  {
    $user = Node::load(array(
      'class' => 'user',
      'deleted' => 0,
      'published' => 1,
      'id' => $ctx->get('id'),
      ));

    $email = $user
      ? $user->email
      : 'john.doe@example.com';

    $gurl = 'http://www.gravatar.com/avatar/' . md5($email);
    if (null !== ($s = $ctx->get('s')))
      $gurl .= '?s=' . intval($s);

    return new Redirect($gurl);
  }
}
