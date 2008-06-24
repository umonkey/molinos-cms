<?php

class DBSchema_node__seq extends TableManager
{
  public function __construct()
  {
    $this->columns['id'] = array(
        'type' => 'int',
        'key' => 'pri',
        'autoincrement' => true
        );

    $this->columns['n'] =  array(
        'type' => 'int',
        'required' => false,
        );
  }

  public  function createTable($table_name)
  {
    parent::createTable($table_name);

    if (!($curid = mcms::db()->getResult("SELECT MAX(`id`) FROM `node`")))
      $curid = 1;

    mcms::db()->exec("INSERT INTO `node__seq` (`id`, `n`) VALUES(:id, 1)", array(':id' => $curid));
  }
}
