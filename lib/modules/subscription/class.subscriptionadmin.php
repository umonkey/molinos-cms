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
}
