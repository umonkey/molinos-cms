<?php

class CommentRPC extends RPCHandler implements iRemoteCall
{
  public static function rpc_add(Context $ctx)
  {
    $ctx->user->checkAccess('c', 'comment');

    $node = Node::create('comment', array(
      'published' => $ctx->user->hasAccess('p', 'comment'),
      ));

    $node->formProcess($ctx->post);

    if ($ctx->post('anonymous')) {
      $node->name = t('Комментарий анонимный');
      $node->uid = null;
    }

    $node->save();
  }
}
