<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SyslogListHandler extends AdminListHandler implements iAdminList
{
  public function __construct(Context $ctx)
  {
    $ctx->theme = os::path('lib', 'modules', 'syslog', 'template.xsl');
    parent::__construct($ctx);
  }

  protected function setUp()
  {
    $this->preset = 'syslog';
    $this->title = t('Системные сообщения');
    $this->actions = array();
    $this->selectors = false;
    $this->noedit = true;
    $this->hidesearch = true;
  }

  protected function getData()
  {
    $offset = ($this->page - 1) * $this->limit;

    $sql = "SELECT `timestamp`, `nid`, `uid`, `username`, `ip`, `operation`, `name` FROM `node__log` ORDER BY `lid` DESC LIMIT {$offset}, {$this->limit}";

    $output = '';
    foreach ($this->ctx->db->getResults($sql) as $row)
      $output .= html::em('node', $row);

    $this->pgcount = $this->ctx->db->getResult("SELECT COUNT(*) FROM `node__log`") * 1;

    return html::em('data', array(
      'mode' => 'syslog',
      ), $output);
  }

  public static function on_get_list(Context $ctx)
  {
    $tmp = new SyslogListHandler($ctx);
    return $tmp->getHTML('syslog');
  }

  public function getNodeActions()
  {
    return null;
  }

  /**
   * @mcms_message ru.molinos.cms.hook.node
   */
  public static function on_node_change(Context $ctx, $node, $op)
  {
    try {
      list($sql, $params) = sql::getInsert('node__log', array(
        'nid' => $node->id,
        'uid' => $ctx->user->id,
        'username' => $ctx->user->name,
        'operation' => $op,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'timestamp' => mcms::now(),
        'name' => $node->name,
        ));

      $ctx->db->exec($sql, $params);
    } catch (TableNotFoundException $e) { }
  }
};
