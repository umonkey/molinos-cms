<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SysLogModule implements iAdminUI, iAdminMenu
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Системный журнал',
      'description' => 'Просмотр операций, выполняемых администраторами сайта.',
      );
  }

  // Обработка GET запросов.
  public static function onGet(RequestContext $ctx)
  {
    $tmp = new SyslogListHandler($ctx);
    return $tmp->getHTML();
  }

  public static function getMenuIcons()
  {
    $icons = array();
    $user = mcms::user();

    $icons[] = array(
      'group' => 'statistics',
      'href' => '/admin/?module=syslog&mode=list',
      'title' => t('Журнал событий'),
      'description' => t('Кто, что, когда и с чем делал.'),
    );

    return $icons;
  }

  public static function createTable()
  {
    $t = new TableInfo('node__log');

    $t->columnSet('lid', array(
      'type' => 'integer',
      'key' => 'pri',
      'autoincrement' => 1,
      ));

    $t->columnSet('nid', array(
      'type' => 'int',
      'required' => false,
      ));
    $t->columnSet('uid', array(
      'type' => 'int',
      'required' => false,
      'key' => 'mul',
      ));
    $t->columnSet('username', array(
      'type' => 'varchar(255)',
      ));
    $t->columnSet('ip', array(
      'type' => 'varchar(64)',
      ));
    $t->columnSet('operation', array(
      'type' => 'varchar(255)',
      ));
    $t->columnSet('timestamp', array(
      'type' => 'datetime',
      ));
    $t->columnSet('message', array(
      'type' => 'text',
      ));
    $t->commit();
  }

  public static function log($op, $message, $nid = null)
  {
    static $conf = null;

    if (null === $conf)
      $conf = mcms::modconf('syslog');

    $curtime = time();
    $utcdiff = date('Z', $curtime);
    $utctime = $curtime-$utcdiff;
    $tm = date('Y-m-d H:i:s', $utctime);

    try {
      mcms::db()->exec("INSERT INTO `node__log` (`nid`, `uid`, `username`, `ip`, `operation`, `timestamp`, `message`) "
        ."VALUES (:nid, :uid, :username, :ip, :operation, '$tm', :message)", array(
        ':nid' => $nid,
        ':uid' => mcms::user()->id,
        ':username' => mcms::user()->name,
        ':ip' => empty($_SERVER['REMOTE_ADDR']) ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'],
        ':operation' => $op,
        ':message' => $message,
        ));

      if (!empty($conf['limit'])) {
        $last = mcms::db()->getResult("SELECT `lid` FROM `node__log` ORDER BY `lid` DESC LIMIT {$conf['limit']}, 1");
        mcms::db()->exec("DELETE FROM `node__log` WHERE `lid` <= :last", array(':last' => $last));
      }
    }

    catch (TableNotFoundException $e) {
      self::createTable();

      SysLogModule::log($op, $message, $nid);
    }
  }
};
