<?php

class AttachmentMenu implements iAdminMenu
{
  public static function getMenuIcons(Context $ctx)
  {
    $icons = array();

    $icons[] = array(
      'group' => 'system',
      'href' => '?action=list&module=attachment',
      'title' => t('Трансформации'),
      'description' => t('Правила трансформации картинок.'),
      );

    return $icons;
  }
}
