<?php

class AvatarRPC extends RPCHandler implements iRemoteCall
{
  protected static function rpc_get_default(Context $ctx)
  {
    $user = Node::load(array(
      'class' => 'user',
      'deleted' => 0,
      'published' => 1,
      'id' => $ctx->get('id'),
      ));

    $gurl = 'http://www.gravatar.com/avatar/' . md5($user->email);
    if (null !== ($s = $ctx->get('s')))
      $gurl .= '?s=' . intval($s);

    return new Redirect($gurl);
  }
}
