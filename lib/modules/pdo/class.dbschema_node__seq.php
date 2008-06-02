<?php

class DBSchema_node__seq
{
  public static function create()
  {
    $t = new TableInfo('node__seq');
    if (!$t->exists()) {
      $t->columnSet('id', array(
        'type' => 'int',
        'key' => 'pri',
        'autoincrement' => true
        ));
      $t->columnSet('n', array(
        'type' => 'int',
        'required' => false,
        ));
      $t->commit();

      if (!($curid = mcms::db()->getResult("SELECT MAX(`id`) FROM `node`")))
        $curid = 1;

      mcms::db()->exec("INSERT INTO `node__seq` (`id`, `n`) VALUES(:id, 1)", array(':id' => $curid));
    }
  }
}
