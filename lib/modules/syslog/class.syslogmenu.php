<?php

class SysLogMenu implements iAdminMenu
{
  public static function getMenuIcons(Context $ctx)
  {
    $icons = array();
    $user = $ctx->user;

    $icons[] = array(
      'group' => 'statistics',
      'href' => '?q=admin&action=list&module=syslog&cgroup=statistics',
      'title' => t('Журнал событий'),
      'description' => t('Кто, что, когда и с чем делал.'),
    );

    return $icons;
  }
}
