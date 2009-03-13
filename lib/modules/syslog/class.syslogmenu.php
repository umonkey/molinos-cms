<?php

class SysLogMenu
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu.enum
   */
  public static function getMenuIcons(Context $ctx, array &$icons)
  {
    $user = $ctx->user;

    $icons[] = array(
      'group' => 'statistics',
      'href' => '?q=admin&action=list&module=syslog&cgroup=statistics',
      'title' => t('Журнал событий'),
      'description' => t('Кто, что, когда и с чем делал.'),
    );
  }
}
