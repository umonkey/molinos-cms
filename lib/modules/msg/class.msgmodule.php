<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class MsgModule implements iRemoteCall
{
  public static function send($from, $to, $subject, $text)
  {
    $schema = TypeNode::getSchema('message');

    $msg = Node::create('message', array(
      'uid' => self::getUid($from),
      're' => self::getUid($to),
      'name' => $subject,
      'text' => $text,
      'received' => null,
      ));

    $msg->save();
  }

  private static function getUid($re)
  {
    if (empty($re))
      return mcms::user()->id;
    elseif ($re instanceof Node)
      return $re->id;
    elseif (is_numeric($re))
      return intval($re);

    throw new InvalidArgumentException(t('Получатель сообщения должен быть указан числом или объектом Node.'));
  }

  public static function hookRemoteCall(RequestContext $ctx)
  {
    $next = null;

    switch ($ctx->get('action')) {
    case 'purge':
      $filter = array(
        'class' => 'message',
        're' => mcms::user()->id,
        '#cache' => false,
        );

      $ids = join(', ', array_keys(Node::find($filter)));

      if (!empty($ids)) {
        mcms::db()->exec("DELETE FROM `node` WHERE `id` IN ({$ids})");
        mcms::flush();
      }

      $next = $ctx->get('destination');
      break;
    }

    mcms::redirect($next);
  }
}
