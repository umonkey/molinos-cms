<?php

class DBSchema_node__log extends TableManager
{
  public function __construct()
  {
    $this->columns['lid'] = array(
        'type' => 'integer',
        'key' => 'pri',
        'autoincrement' => 1,
        );

    $this->columns['nid'] = array(
        'type' => 'int',
        'required' => false,
        );

    $this->columns['uid'] = array(
        'type' => 'int',
        'required' => false,
        'key' => 'mul',
        );

    $this->columns['username'] = array(
        'type' => 'varchar(255)',
        );

    $this->columns['ip'] = array(
        'type' => 'varchar(64)',
        );

    $this->columns['operation'] = array(
        'type' => 'varchar(255)',
        );

    $this->columns['timestamp'] =  array(
        'type' => 'datetime',
        );

    $this->columns['message'] = array(
       'type' => 'text',
        );
  }
}
