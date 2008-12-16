<?php

class DBSchema_node__poll extends TableManager
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
        'type' => 'varchar(15)',
        'required' => true,
        'key' => 'mul',
        );

    $this->columns['option'] = array(
        'type' => 'int',
        'required' => true,
        'key' => 'mul',
        );
  }
}
