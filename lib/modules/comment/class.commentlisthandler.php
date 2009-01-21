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
  }
};
