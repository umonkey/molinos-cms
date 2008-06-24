<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentListHandler extends AdminListHandler implements iAdminUI, iAdminMenu
{
  public static function onGet(RequestContext $ctx)
  {
    $tmp = new CommentListHandler($ctx);
    $output = $tmp->getHTML('comments');

    return $output;
  }

  protected function setUp($preset = null)
  {
    $this->types = array('comment');
    $this->title = t('Комментарии пользователей');
    $this->columns = array('name', 'uid', 'created');
    $this->actions = array('publish', 'unpublish', 'delete');
  }

  public static function getMenuIcons()
  {
    $icons = array();

    if (mcms::user()->hasAccess('u', 'comment') and Node::count(array('class' => 'comment')))
      $icons[] = array(
        'group' => 'content',
        'href' => 'admin?module=comment',
        'title' => t('Комментарии'),
        'description' => t('Управление комментариями пользователей.'),
        );

    return $icons;
  }
};
