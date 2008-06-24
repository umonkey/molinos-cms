<?php

class DBSchema_node__session extends TableManager
{
  public function __construct()
  {
    $this->columns['sid'] = array(
        'type' => 'char(32)',
        'required' => true,
        'key' => 'pri',
        );

    $this->columns['created'] = array(
        'type' => 'datetime',
        'required' => true,
        'key' => 'mul',
        );

    $this->columns['data'] =  array(
        'type' => 'blob',
        'required' => true,
       );
  }
}
