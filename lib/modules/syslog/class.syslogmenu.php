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
      'href' => '?action=list&module=syslog',
      'title' => t('Журнал событий'),
      'description' => t('Кто, что, когда и с чем делал.'),
    );
  }
}
