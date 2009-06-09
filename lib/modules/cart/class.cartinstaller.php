<?php

class CartInstaller
{
  /**
   * @mcms_message ru.molinos.cms.install
   */
  public static function onInstall(Context $ctx)
  {
    $count = Node::count(array(
      'class' => 'type',
      'name' => 'order',
      'deleted' => 0,
      ), $ctx->db);

    if (!$count) {
      $ctx->db->beginTransaction();

      Node::create(array(
        'class' => 'type',
        'name' => 'order',
        'title' => 'Заказ',
        ))->save();

      $ctx->db->commit();
    }
  }
}
