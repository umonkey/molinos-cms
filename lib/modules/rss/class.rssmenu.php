<?php

class RSSMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();

    if (Context::last()->user->hasAccess('u', 'rssfeed'))
      $icons[] = array(
        'group' => 'structure',
        'href' => '?action=list&module=rss',
        'title' => t('RSS ленты'),
        'description' => t('Управление экспортируемыми данными.'),
        );

    return $icons;
  }
}
