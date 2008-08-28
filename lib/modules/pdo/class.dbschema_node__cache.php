<?php

class DBSchema_node__cache extends TableManager
{
  public function __construct()
  {
    $this->columns['cid'] =  array(
      'type' => 'char(32)',
      'required' => true,
      'key' => 'pri',
      );

    $this->columns['data'] =  array(
      'type' => 'mediumblob',
      );
  }
}
