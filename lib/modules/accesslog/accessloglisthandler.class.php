<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AccessLogListHandler extends AdminListHandler
{
  protected function setUp()
  {
    $this->title = t('Статистика доступа');
    $this->columns = array('timestamp', 'nid', 'ip', 'referer');
    $this->actions = array();
  }

  protected function getData()
  {
    $data = mcms::db()->getResults("SELECT * FROM `node__astat` ORDER BY `id` DESC");

    return $data;
  }
};
