<?php

class SysLogMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();
    $user = mcms::user();

    $icons[] = array(
      'group' => 'statistics',
      'href' => '?q=admin&action=list&module=syslog&cgroup=statistics',
      'title' => t('Журнал событий'),
      'description' => t('Кто, что, когда и с чем делал.'),
    );

    return $icons;
  }
}
