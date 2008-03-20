<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SyslogListHandler extends AdminListHandler
{
  protected function setUp()
  {
    $this->title = t('Системные сообщения');
    $this->columns = array('timestamp', 'nid', 'uid', 'username', 'ip', 'operation', 'message');
    $this->actions = array();
    $this->selectors = false;
  }

  protected function getData()
  {
    $offset = ($this->page - 1) * $this->limit;

    if (null === ($tmp = $this->ctx->get('search')))
      $data = mcms::db()->getResults("SELECT * FROM `node__log` ORDER BY `lid` DESC LIMIT {$offset}, {$this->limit}");
    else
      $data = mcms::db()->getResults("SELECT * FROM `node__log` WHERE `message` LIKE :search ORDER BY `lid` DESC LIMIT {$offset}, {$this->limit}", array(':search' => '%'. $tmp .'%'));

    return $data;
  }
};
