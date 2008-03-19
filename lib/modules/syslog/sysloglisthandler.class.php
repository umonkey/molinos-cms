<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SyslogListHandler extends AdminListHandler
{
  protected function setUp()
  {
    $this->title = t('Системные сообщения');
    $this->columns = array('timestamp', 'nid', 'uid', 'username', 'ip', 'operation', 'message');
    $this->actions = array();
  }

  protected function getData()
  {
    $data = mcms::db()->getResults("SELECT * FROM `node__log` ORDER BY `lid` DESC");
    return $data;
  }
};
