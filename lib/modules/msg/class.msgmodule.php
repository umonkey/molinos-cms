<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class MsgModule
{
  public static function send($from, $to, $subject, $text)
  {
    if (null === $from)
      $from = mcms::user()->id;
    elseif ($from instanceof Node)
      $from = $from->id;
    elseif (!is_numeric($from))
      throw new InvalidArgumentException(t('Получатель сообщения должен быть объектом или числовым идентификатором.'));

    $schema = TypeNode::getSchema('message');

    $msg = Node::create('message', array(
      'uid' => $from,
      're' => $to,
      'name' => $subject,
      'text' => $text,
      ));

    $msg->save();
  }
}
