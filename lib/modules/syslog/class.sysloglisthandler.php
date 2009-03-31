<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SyslogListHandler extends AdminListHandler implements iAdminList
{
  protected function setUp()
  {
    $this->title = t('Системные сообщения');
    $this->columns = array('timestamp', 'nid', 'uid', 'username', 'ip', 'operation', 'message');
    $this->actions = array();
    $this->selectors = false;
    $this->noedit = true;
  }

  public function __construct(Context $ctx)
  {
    parent::__construct($ctx);
  }

  protected function getData()
  {
    $offset = ($this->page - 1) * $this->limit;

    $sql = "SELECT `timestamp`, `nid`, `uid`, `username`, `ip`, `operation`, `message` FROM `node__log` ORDER BY `lid` DESC LIMIT {$offset}, {$this->limit}";

    $data = $this->ctx->db->getResults($sql);
    $this->pgcount = $this->ctx->db->getResult("SELECT COUNT(*) FROM `node__log`") * 1;

    return $data;
  }

  /**
   * Рендерит список подписавшихся пользователей.
   * 
   * @param Context $ctx 
   * @return string
   * @mcms_message ru.molinos.cms.admin.list.syslog
   */
  public static function on_get_list(Context $ctx)
  {
    $class = __CLASS__;
    $tmp = new $class($ctx);
    return $tmp->getHTML($ctx->get('preset'));
  }
};
