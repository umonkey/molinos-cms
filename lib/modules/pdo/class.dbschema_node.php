<?php

class DBSchema_node
{
  public static function create()
  {
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
  }
}
