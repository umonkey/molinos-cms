<?php

class DBSchema_node__rev extends TableManager
{
  public function __construct()
  {
    $this->columns['rid'] = array(
        'type' => 'integer',
        'required' => 1,
        'key' => 'pri',
        'autoincrement' => 1,
        );

    $this->columns['nid'] = array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul'
        );

    $this->columns['uid'] = array(
        'type' => 'int',
        'required' => 0,
        'key' =>'mul'
        );

    $this->columns['name'] = array(
        'type' => 'varchar(255)',
        'required' => 0,
        'key' =>'mul'
        );

    // Название, приведённое к нижнему регистру.  Используется для поиска в
    // SQLite, где есть только один режим сравнения: binary.
    $this->columns['name_lc'] = array(
        'type' => 'varchar(255)',
        'required' => 0,
        'key' =>'mul'
        );

    $this->columns['data'] = array(
        'type' => 'mediumblob',
        'required' => 0,
        );

    $this->columns['created'] = array(
        'type' => 'datetime',
        'required' => 1,
        'key' =>'mul'
        );
  }
}
