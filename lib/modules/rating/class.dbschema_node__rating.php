<?php

class DBSchema_node__rating extends TableManager
{
  public function __construct()
  {
    $this->columns['nid'] = array(
        'type' => 'int',
        'required' => true,
        'key' => 'mul',
        );

    $this->columns['uid'] = array(
        'type' => 'int',
        'required' => false,
        'key' => 'mul',
        );

    $this->columns['ip'] = array(
        'required' => true,
        'type' => 'varchar(64)',
        'key' => 'mul',
        );

    $this->columns['rate'] = array(
        'required' => true,
        'type' => 'decimal(5,0)',
        'key' => 'mul',
        );

    $this->columns['sid'] =  array(
        'type' => 'varchar(255)',
        'key' => 'mul',
        );
  }
}
