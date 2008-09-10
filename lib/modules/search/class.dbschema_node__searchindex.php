<?php

class DBSchema_node__searchindex extends TableManager
{
  public function __construct()
  {
    $this->columns['nid'] = array(
      'type' => 'int',
      'required' => true,
      'key' => 'mul',
      );
    $this->columns['url'] = array(
      'type' => 'varchar(255)',
      'required' => true,
      );
    $this->columns['html'] = array(
      'type' => 'mediumblob',
      );
  }
}
