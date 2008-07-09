<?php

class DBSchema_node__sessions extends TableManager
{
  public function __construct()
  {
    $this->columns['sid'] = array(
        'type' => 'char(40)',
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
