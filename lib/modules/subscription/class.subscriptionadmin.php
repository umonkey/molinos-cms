<?php

class SubscriptionAdmin
{
  /**
   * Инсталляция типа документа subscription.
   *
   * @mcms_message ru.molinos.cms.install
   */
  public static function onInstall(Context $ctx)
  {
    try {
      $node = Node::load(array(
        'class' => 'type',
        'name' => 'subscription',
        'deleted' => 0,
        ), $ctx->db);
    } catch (ObjectNotFoundException $e) {
      $ctx->db->beginTransaction();
      $node = Node::create('type', array(
        'name' => 'subscription',
        'title' => t('Подписка на новости'),
        ))->save();
      $ctx->db->commit();
    }
  }
}
