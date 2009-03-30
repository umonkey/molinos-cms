<?php

class MaintenanceMenu
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu.enum
   */
  public static function getMenuIcons(Context $ctx, array &$icons)
  {
    $user = $ctx->user;

    if ($user->hasAccess('u', 'domain'))
      $icons[] = array(
        'group' => 'system',
        'img' => 'img/cms-maintenance.png',
        'href' => '?action=form&module=modman&mode=config&name=maintenance&cgroup=system&destination=CURRENT',
        'title' => t('Профилактика'),
        'description' => t('Позволяет временно закрыть сайт для проведения технических работ.'),
        );
  }
}
