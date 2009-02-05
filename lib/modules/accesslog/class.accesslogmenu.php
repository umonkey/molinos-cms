<?php

class AccessLogMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();
    $user = Context::last()->user;

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
