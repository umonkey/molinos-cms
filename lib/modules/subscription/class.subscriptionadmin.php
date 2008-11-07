<?php

class SubscriptionAdmin implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();
    $user = mcms::user();

    if ($user->hasAccess('u', 'user'))
      $icons[] = array(
        'group' => 'content',
        'href' => '?q=admin&module=subscription',
        'title' => t('Рассылка'),
        'description' => t('Управление подпиской на новости.'),
        );

    return $icons;
  }
}
