<?php

class MaintenanceMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();
    $user = mcms::user();

    if ($user->hasAccess('u', 'domain'))
      $icons[] = array(
        'group' => 'system',
        'img' => 'img/cms-maintenance.png',
        'href' => '?q=admin&cgroup=system&module=modman&mode=config&name=maintenance&destination=CURRENT',
        'title' => t('Профилактика'),
        'description' => t('Позволяет временно закрыть сайт для проведения технических работ.'),
        );

    return $icons;
  }
}
