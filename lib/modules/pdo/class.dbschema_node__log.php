<?php

class DBSchema_node__log
{
  public static function create()
  {
    $t = new TableInfo('node__log');

    if (!$t->exists()) {
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
  }
}
