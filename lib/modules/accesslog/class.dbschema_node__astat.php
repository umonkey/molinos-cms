<?php

class DBSchema_node__astat extends TableManager
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

    $this->columns['ip'] = array(
        'type' => 'varchar(64)',
        );

    $this->columns['referer'] = array(
        'type' => 'varchar(255)',
        );

    $this->columns['timestamp'] =  array(
        'type' => 'datetime',
        );
  }
}
