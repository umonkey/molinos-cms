<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RSSListHandler extends AdminListHandler
{
  protected function setUp($preset = null)
  {
    $this->types = array('rssfeed');
    $this->title = t('Исходящие RSS потоки');
    $this->columns = array('name', 'uid', 'created');
    $this->actions = array('publish', 'unpublish', 'delete');
  }
};
