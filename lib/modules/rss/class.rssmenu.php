<?php

class RSSMenu
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu.enum
   */
  public static function getMenuIcons(Context $ctx, array &$icons)
  {
    if ($ctx->user->hasAccess('u', 'rssfeed'))
      $icons[] = array(
        'group' => 'structure',
        'href' => '?action=list&module=rss',
        'title' => t('RSS ленты'),
        'description' => t('Управление экспортируемыми данными.'),
        );
  }
}
