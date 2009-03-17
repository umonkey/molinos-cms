<?php

class BaseInstaller
{
  /**
   * @mcms_message ru.molinos.cms.install
   */
  public static function onInstall(Context $ctx)
  {
    $t = new TableInfo($ctx->db, 'node');
    $t->columnSet('id', array(
      'type' => 'integer',
      'required' => true,
      'key' => 'pri',
      ));
    $t->columnSet('lang', array(
      'type' => 'char(4)',
      'required' => true,
      'key' => 'mul',
      ));
    $t->columnSet('parent_id', array(
      'type' => 'int',
      'required' => false,
      'key' => 'mul',
      ));
    $t->columnSet('class', array(
      'type' => 'varchar(16)',
      'required' => true,
      'key' => 'mul',
      ));
    $t->columnSet('left', array(
      'type' => 'int',
      'required' => false,
      'key' => 'mul',
      ));
    $t->columnSet('right', array(
      'type' => 'int',
      'required' => false,
      'key' => 'mul',
      ));
    $t->columnSet('uid', array(
      'type' => 'int',
      'required' => false,
      'key' => 'mul',
      ));
    $t->columnSet('created', array(
      'type' => 'datetime',
      'required' => true,
      'key' => 'mul',
      ));
    $t->columnSet('updated', array(
      'type' => 'datetime',
      'required' => true,
      'key' => 'mul',
      ));
    $t->columnSet('deleted', array(
      'type' => 'tinyint(1)',
      'required' => true,
      'default' => 0,
      'key' => 'mul',
      ));
    $t->columnSet('published', array(
      'type' => 'tinyint(1)',
      'required' => true,
      'default' => 0,
      'key' => 'mul',
      ));
    $t->columnSet('name', array(
      'type' => 'varchar(255)',
      'required' => false,
      'key' => 'mul',
      ));
    $t->columnSet('data', array(
      'type' => 'mediumblob',
      ));
    $t->commit();

    $t = new TableInfo($ctx->db, 'node__rel');
    $t->columnSet('nid', array(
      'type' => 'int',
      'required' => true,
      'key' => 'mul',
      ));
    $t->columnSet('tid', array(
      'type' => 'int',
      'required' => true,
      'key' => 'mul',
      ));
    $t->columnSet('key', array(
      'type' => 'varchar(255)',
      'key' => 'mul',
      ));
    $t->columnSet('order', array(
      'type' => 'int',
      'key' => 'mul',
      ));
    $t->commit();

    $t = new TableInfo($ctx->db, 'node__access');
    $t->columnSet('nid', array(
      'type' => 'int',
      'required' => true,
      'key' => 'mul',
      ));
    $t->columnSet('uid', array(
      'type' => 'int',
      'required' => true,
      'key' => 'mul',
      ));
    $t->columnSet('c', array(
      'type' => 'tinyint(1)',
      'required' => true,
      'default' => 0,
      'key' => 'mul',
      ));
    $t->columnSet('r', array(
      'type' => 'tinyint(1)',
      'required' => true,
      'default' => 0,
      'key' => 'mul',
      ));
    $t->columnSet('u', array(
      'type' => 'tinyint(1)',
      'required' => true,
      'default' => 0,
      'key' => 'mul',
      ));
    $t->columnSet('d', array(
      'type' => 'tinyint(1)',
      'required' => true,
      'default' => 0,
      'key' => 'mul',
      ));
    $t->columnSet('p', array(
      'type' => 'tinyint(1)',
      'required' => true,
      'default' => 0,
      'key' => 'mul',
      ));
    $t->commit();

    $t = new TableInfo($ctx->db, 'node__session');
    $t->columnSet('sid', array(
      'type' => 'char(32)',
      'required' => true,
      'key' => 'pri',
      ));
    $t->columnSet('created', array(
      'type' => 'datetime',
      'required' => true,
      'key' => 'mul',
      ));
    $t->columnSet('data', array(
      'type' => 'mediumblob',
      'required' => true,
      ));
    $t->commit();

    $t = new TableInfo($ctx->db, 'node__fallback');
    $t->columnSet('old', array(
      'type' => 'varchar(255)',
      'required' => true,
      'key' => 'uni',
      ));
    $t->columnSet('new', array(
      'type' => 'varchar(255)',
      ));
    $t->columnSet('ref', array(
      'type' => 'varchar(255)',
      ));
    $t->commit();
  }
}
