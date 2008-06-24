<?php

class DBSchema_node extends TableManager
{
  public function __construct()
  {
    $this->columns['id'] = array(
        'type' => 'int',
        'required' => true,
        'key' => 'mul',
        );

    $this->columns['lang'] = array(
        'type' => 'char(4)',
        'required' => true,
        'key' => 'mul',
        );

    $this->columns['rid'] = array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul',
        );

    $this->columns['parent_id'] = array(
        'type' => 'int',
        'required' => 0,
        );

    $this->columns['class'] = array(
        'type' => 'varchar(16)',
        'required' => 1,
        'key' => 'mul'
        );

    $this->columns['code'] =  array(
        'type' => 'varchar(16)',
        'required' => 0,
        'key' => 'uni'
        );

    $this->columns['left'] = array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul'
        );

    $this->columns['right'] = array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul'
        );

    $this->columns['uid'] = array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul'
        );

    $this->columns['created'] = array(
        'type' => 'datetime',
        'required' => 0,
        'key' => 'mul'
        );

    $this->columns['updated'] = array(
        'type' => 'datetime',
        'required' => 0,
        'key' => 'mul'
        );

    $this->columns['published'] = array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        'key' => 'mul'
        );

    $this->columns['deleted'] = array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        'key' => 'mul'
        );
  }
}
