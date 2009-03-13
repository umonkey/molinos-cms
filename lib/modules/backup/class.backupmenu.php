<?php

class BackupMenu
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu.enum
   */
  public static function getMenuIcons(Context $ctx, array &$icons)
  {
    $icons[] = array(
      'group' => 'system',
      'href' => '?action=form&module=backup',
      'title' => t('Архив сайта'),
      );
  }
}
