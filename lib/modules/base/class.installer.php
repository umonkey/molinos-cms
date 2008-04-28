<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Installer
{
  static function CreateTables()
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
      $t->columnSet('code', array(
        'type' => 'varchar(16)',
        'required' => 0,
        'key' => 'uni'
        ));

      $t->columnSet('left', array(
        'type' => 'int',
        'required' => 1,
        'key' => 'mul'
        ));
      $t->columnSet('right', array(
        'type' => 'int',
        'required' => 1,
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
  }
}
