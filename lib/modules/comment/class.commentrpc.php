<?php

class CommentRPC extends RPCHandler
{
  public static function on_rpc(Context $ctx)
  {
    parent::hookRemoteCall($ctx, __CLASS__);
  }

  public static function rpc_post_add(Context $ctx)
  {
    $ctx->user->checkAccess(ACL::CREATE, 'comment');

    $node = Node::create('comment', array(
      'published' => $ctx->user->hasAccess(ACL::PUBLISH, 'comment'),
      ));

    $node->formProcess($ctx->post);

    if ($ctx->post('anonymous')) {
      $node->name = t('Комментарий анонимный');
      $node->uid = null;
    }

    $node->save();
  }
}
