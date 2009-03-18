<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class MsgModule extends RPCHandler
{
  /**
   * @mcms_message ru.molinos.cms.rpc.msg
   */
  public static function on_rpc(Context $ctx)
  {
    return parent::hookRemoteCall($ctx, __CLASS__);
  }

  public static function send($from, $to, $subject, $text)
  {
    $msg = Node::create('message', array(
      'uid' => self::getUid($from),
      're' => self::getUid($to),
      'name' => $subject,
      'text' => $text,
      'received' => null,
      ));

    return $msg->save();
  }

  private static function getUid($re)
  {
    if (empty($re))
      return Context::last()->user->id;
    elseif ($re instanceof Node)
      return $re->id;
    elseif (is_numeric($re))
      return intval($re);

    if (is_array($re) and count($re) == 1)
      return self::getUid(array_shift($re));

    throw new InvalidArgumentException(t('Получатель сообщения должен быть указан числом или объектом Node.'));
  }

  public static function rpc_purge(Context $ctx)
  {
    $filter = array(
      'class' => 'message',
      're' => $ctx->user->id,
      );

    $ids = join(', ', array_keys(Node::find($ctx->db, $filter)));

    if (!empty($ids)) {
      $ctx->db->exec("DELETE FROM `node` WHERE `id` IN ({$ids})");
      mcms::flush();
    }
  }
}
