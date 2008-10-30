<?php

class CommentRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    return mcms::dispatch_rpc(__CLASS__, $ctx);
  }

  public static function rpc_add(Context $ctx)
  {
    mcms::user()->checkAccess('c', 'comment');

    $node = Node::create('comment', array(
      'published' => mcms::user()->hasAccess('p', 'comment'),
      ));

    $node->formProcess($ctx->post);

    if ($ctx->post('anonymous')) {
      $node->name = t('Комментарий анонимный');
      $node->uid = null;
    }

    $node->save();
  }
}
