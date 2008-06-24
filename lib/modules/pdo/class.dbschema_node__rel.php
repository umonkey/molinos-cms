<?php

class DBSchema_node__rel extends TableManager
{
  public function __construct()
  {
    $this->columns['nid'] = array(
        'type' => 'int',
        'required' => 1,
        'key' => 'mul'
        );

    $this->columns['tid'] = array(
        'type' => 'int',
        'required' => 1,
        'key' => 'mul'
        );

    $this->columns['key'] = array(
        'type' => 'varchar(255)',
        'required' => 0,
        'key' =>'mul'
        );

    $this->columns['order'] = array(
        'type' => 'int',
        'required' => 0,
        'key' =>'mul'
        );
  }
}
