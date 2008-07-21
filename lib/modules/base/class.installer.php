<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Installer
{
  public static function CreateTables()
  {
    mcms::db()->clearDB();
    $t = new TableInfo('node');

    if (!$t->exists()) {
      $t->columnSet('id', array(
        'type' => 'int',
        'required' => true,
        'key' => 'mul',
        ));
      $t->columnSet('lang', array(
        'type' => 'char(4)',
        'required' => true,
        'key' => 'mul',
        ));
      $t->columnSet('rid', array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul',
        ));
      $t->columnSet('parent_id', array(
        'type' => 'int',
        'required' => 0,
        ));
      $t->columnSet('class', array(
        'type' => 'varchar(16)',
        'required' => 1,
        'key' => 'mul'
        ));

      $t->columnSet('left', array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul'
        ));
      $t->columnSet('right', array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul'
       ));
      $t->columnSet('uid', array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul'
        ));
     $t->columnSet('created', array(
        'type' => 'datetime',
        'required' => 0,
        'key' => 'mul'
        ));
     $t->columnSet('updated', array(
        'type' => 'datetime',
        'required' => 0,
        'key' => 'mul'
        ));
     $t->columnSet('published', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        'key' => 'mul'
        ));
     $t->columnSet('deleted', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        'key' => 'mul'
        ));
      $t->commit();
    }

    $t = new TableInfo('node__rel');
    if (!$t->exists()) {
      $t->columnSet('nid', array(
        'type' => 'int',
        'required' => 1,
        'key' => 'mul'
        ));
      $t->columnSet('tid', array(
        'type' => 'int',
        'required' => 1,
        'key' => 'mul'
        ));
      $t->columnSet('key', array(
        'type' => 'varchar(255)',
        'required' => 0,
        'key' =>'mul'
        ));
      $t->columnSet('order', array(
        'type' => 'int',
        'required' => 0,
        'key' =>'mul'
        ));
       $t->commit();
    }

    $t = new TableInfo('node__rev');
    if (!$t->exists()) {
      $t->columnSet('rid', array(
        'type' => 'integer',
        'required' => 1,
        'key' => 'pri',
        'autoincrement' => 1,
        ));
      $t->columnSet('nid', array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul'
        ));
      $t->columnSet('uid', array(
        'type' => 'int',
        'required' => 0,
        'key' =>'mul'
        ));
      $t->columnSet('name', array(
        'type' => 'varchar(255)',
        'required' => 0,
        'key' =>'mul'
        ));
      $t->columnSet('data', array(
        'type' => 'mediumblob',
        'required' => 0,
       ));

      $t->columnSet('created', array(
        'type' => 'datetime',
        'required' => 1,
        'key' =>'mul'
        ));
      $t->commit();
    }

    $t = new TableInfo('node__access');
    if (!$t->exists()) {
      $t->columnSet('nid', array(
        'type' => 'int',
        'required' => 1,
        'key' => 'mul',
        ));
      $t->columnSet('uid', array(
        'type' => 'int',
        'required' => 1,
        'key' => 'mul'
        ));
      $t->columnSet('c', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        ));
      $t->columnSet('r', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        ));
      $t->columnSet('u', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        ));
      $t->columnSet('d', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        ));
       $t->commit();
    }

    $t = new TableInfo('node__cache');
    if (!$t->exists()) {
      $t->columnSet('cid', array(
        'type' => 'char(32)',
        'required' => true,
        ));
      $t->columnSet('lang', array(
        'type' => 'char(2)',
        'required' => true,
        ));
      $t->columnSet('data', array(
        'type' => 'mediumblob',
        ));
      $t->commit();
    }

    $t = new TableInfo('node__seq');
    if (!$t->exists()) {
      $t->columnSet('id', array(
        'type' => 'int',
        'required' => true,
        'key' => 'pri',
        'autoincrement' => true
        ));
      $t->commit();
    }
  }

  public static function writeConfig(array $data,$olddsn = null)
  {
    if (empty($data['confirm']))
      throw new InvalidArgumentException("Вы не подтвердили свои намерения.");

    $config = BebopConfig::getInstance();

    if ($olddsn)
      $config->set('default_backup', $olddsn, 'db');

    switch ($data['db']['type']) {
    case 'sqlite':
      if (empty($data['db']['name']))
        $data['db']['name'] = 'default.db';
      elseif (substr($data['db']['name'], -3) != '.db')
        $data['db']['name'] .= '.db';
      $config->set('default', 'sqlite:conf/'. $data['db']['name'], 'db');
      break;

    case 'mysql':
      $config->set('default', sprintf('mysql://%s:%s@%s/%s', $data['db']['user'], $data['db']['pass'], $data['db']['host'], $data['db']['name']), 'db');
      break;
    }

    foreach (self::getDefaults() as $k => $v) {
      $value = array_key_exists($k, $data['config']) ? $data['config'][$k] : $v;
      $config->set($k, $value);
    }

    $config->write();
  }

  private static function getDefaults()
  {
    return array(
      'mail_from' => 'no-reply@cms.molinos.ru',
      'mail_server' => 'localhost',
      'backtracerecipients' => 'cms-bugs@molinos.ru',
      'debuggers' => '127.0.0.1',
      'filestorage' => 'storage',
      'tmpdir' => 'tmp',
      'file_cache_ttl' => 3600,
      );
  }
}
