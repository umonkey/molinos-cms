<?php

class AuthBasicRPC extends RPCHandler
{
  /**
   * @mcms_message ru.molinos.cms.rpc.authbasic
   */
  public static function on_rpc(Context $ctx)
  {
    return parent::hookRemoteCall($ctx, __CLASS__);
  }

  public static function rpc_get_restore(Context $ctx)
  {
    $node = Node::load(array(
      'class' => 'user',
      'name' => $ctx->get('email'),
      'deleted' => 0,
      'published' => 1,
      ));

    if ($ctx->get('otp') != $node->otp)
      throw new ForbiddenException(t('Эта ссылка устарела.'));

    $ctx->db->beginTransaction();
    unset($node->otp);
    $node->save();
    $ctx->db->commit();

    User::authorize($node->name, null, $ctx, true);
    mcms::flog($node->name . ' logged in using OTP.');
  }
}
