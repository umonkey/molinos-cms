<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentListHandler extends AdminListHandler implements iAdminList
{
  protected function setUp($preset = null)
  {
    $this->types = array('comment');
    $this->title = t('Комментарии пользователей');
    $this->columns = array('name', 'uid', 'created');
    $this->actions = array('publish', 'unpublish', 'delete');
    $this->deleted = 0;
  }

  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu()
  {
    return array(
      array(
        're' => 'admin/content/comments',
        'method' => 'on_list',
        'title' => t('Комментарии'),
        ),
      );
  }

  public static function on_list(Context $ctx)
  {
    $list = new CommentListHandler($ctx);
    return $list->getHTML();
  }
};
