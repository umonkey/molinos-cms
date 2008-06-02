<?php

class DBSchema_node__cache
{
  public static function create()
  {
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
