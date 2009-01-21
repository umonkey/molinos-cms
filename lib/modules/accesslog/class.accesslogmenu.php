<?php

class AccessLogMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();
    $user = mcms::user();

    if ($user->hasAccess('u', 'user')) {
      $icons[] = array(
        'group' => 'statistics',
        'href' => '?action=list&module=accesslog',
        'title' => t('Доступ к контенту'),
        'description' => t('Просмотр статистики доступа.'),
        );
    }

    return $icons;
  }
}
