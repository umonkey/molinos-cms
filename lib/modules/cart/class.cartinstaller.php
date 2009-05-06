<?php

class CartInstaller
{
  /**
   * @mcms_message ru.molinos.cms.install
   */
  public static function onInstall(Context $ctx)
  {
    $count = Node::count($ctx->db, array(
      'class' => 'type',
      'name' => 'order',
      'deleted' => 0,
      ));

    if (!$count) {
      $ctx->db->beginTransaction();

      Node::create('type', array(
        'name' => 'order',
        'title' => 'Заказ',
        ))->save();

      $ctx->db->commit();
    }
  }
}
