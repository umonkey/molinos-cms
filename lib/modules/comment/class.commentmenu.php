<?php

class CommentMenu
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu.enum
   */
  public static function getMenuIcons(Context $ctx, array &$icons)
  {
    if ($ctx->user->hasAccess('u', 'comment')) {
      if ($ctx->db->getResult("SELECT COUNT(*) FROM `node` WHERE `class` = 'comment' AND `deleted` = 0")) {
        $icons[] = array(
          'group' => 'content',
          'href' => '?action=list&module=comment',
          'title' => t('Комментарии'),
          'description' => t('Управление комментариями пользователей.'),
          );
      }
    }
  }
}
