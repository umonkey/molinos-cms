<?php

class DBSchema_node__access
{
  public static function create()
  {
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
      $t->columnSet('p', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        ));

       $t->commit();
    }
  }
}
