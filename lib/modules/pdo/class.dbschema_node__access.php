<?php

class DBSchema_node__access extends TableManager
{
  public function __construct()
  {
    $this->columns['nid'] = array(
      'type' => 'int',
      'required' => 1,
      'key' => 'mul',
      );

    $this->columns['uid'] = array(
      'type' => 'int',
      'required' => 1,
      'key' => 'mul'
      );

    $this->columns['c'] = array(
      'type' => 'tinyint(1)',
      'required' => 1,
      'default' => 0,
      );

    $this->columns['r'] = array(
      'type' => 'tinyint(1)',
      'required' => 1,
      'default' => 0,
      );

    $this->columns['u'] = array(
      'type' => 'tinyint(1)',
      'required' => 1,
      'default' => 0,
      );

    $this->columns['d'] = array(
      'type' => 'tinyint(1)',
      'required' => 1,
      'default' => 0,
      );

    $this->columns['p'] = array(
      'type' => 'tinyint(1)',
      'required' => 1,
      'default' => 0,
      );
  }
}
