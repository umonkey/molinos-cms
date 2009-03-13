<?php

class AttachmentMenu
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu.enum
   */
  public static function getMenuIcons(Context $ctx, array &$icons)
  {
    $icons[] = array(
      'group' => 'system',
      'href' => '?action=list&module=attachment',
      'title' => t('Трансформации'),
      'description' => t('Правила трансформации картинок.'),
      );
  }
}
