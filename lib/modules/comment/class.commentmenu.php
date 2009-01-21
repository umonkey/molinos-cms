<?php

class CommentMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();

    if (mcms::user()->hasAccess('u', 'comment') and Node::count(array('class' => 'comment')))
      $icons[] = array(
        'group' => 'content',
        'href' => '?action=list&module=comment',
        'title' => t('Комментарии'),
        'description' => t('Управление комментариями пользователей.'),
        );

    return $icons;
  }
}
