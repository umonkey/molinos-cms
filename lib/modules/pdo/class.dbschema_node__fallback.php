<?php

class DBSchema_node__fallback extends TableManager
{
  public function __construct()
  {
    $this->columns['old'] = array(
      'type' => 'varchar(255)',
      'required' => 1,
      'key' => 'uni',
      );

    $this->columns['new'] = array(
      'type' => 'varchar(255)',
      'required' => 1,
      );
  }
}
