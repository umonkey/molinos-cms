<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AccessLogListHandler extends AdminListHandler
{
  protected function setUp()
  {
    $this->title = t('Статистика доступа');
    $this->columns = array('timestamp', 'nid', 'ip', 'referer');
    $this->actions = array();
    $this->selectors = false;
  }

  protected function getData()
  {
    $offset = ($this->page - 1) * $this->limit;

    $data = $this->ctx->db->getResults("SELECT * FROM `node__astat` ORDER BY `id` DESC LIMIT {$offset}, {$this->limit}");

    return $data;
  }
};
