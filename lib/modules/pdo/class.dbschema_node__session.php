<?php

class DBSchema_node__session
{
  public static function create()
  {
    $t = new TableInfo('node__session');

    if (!$t->exists()) {
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
        'type' => 'blob',
        'required' => true,
        ));
       $t->commit();
    }
  }
}
