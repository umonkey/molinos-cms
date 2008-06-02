<?php

class DBSchema_node__rel
{
  public static function create()
  {
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
  }
}
