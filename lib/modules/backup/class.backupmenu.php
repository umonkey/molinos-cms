<?php

class BackupMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();

    $icons[] = array(
      'group' => 'system',
      'href' => '?q=admin&cgroup=system&module=backup',
      'title' => t('Архив сайта'),
      );

    return $icons;
  }
}
