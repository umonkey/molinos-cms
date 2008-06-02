<?php

class DBSchema_node__rev
{
  public static function create()
  {
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
  }
}
