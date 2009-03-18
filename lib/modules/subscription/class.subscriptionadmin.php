<?php

class SubscriptionAdmin
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu.enum
   */
  public static function getMenuIcons(Context $ctx, array &$icons)
  {
    $user = $ctx->user;

    if ($user->hasAccess('u', 'user'))
      $icons[] = array(
        'group' => 'content',
        'href' => '?action=list&module=subscription',
        'title' => t('Рассылка'),
        'description' => t('Управление подпиской на новости.'),
        );
  }

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
