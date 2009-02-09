<?php

class SubscriptionAdmin implements iAdminMenu
{
  public static function getMenuIcons(Context $ctx)
  {
    $icons = array();
    $user = $ctx->user;

    if ($user->hasAccess('u', 'user'))
      $icons[] = array(
        'group' => 'content',
        'href' => '?action=list&module=subscription',
        'title' => t('Рассылка'),
        'description' => t('Управление подпиской на новости.'),
        );

    return $icons;
  }
}
