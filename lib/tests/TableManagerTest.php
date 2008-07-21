<?php

class TableManagerTest extends PHPUnit_Framework_TestCase
{
  public function testInternalTables()
  {
    $tables = array(
      'node',
      'node__access',
      'node__cache',
      'node__log',
      'node__rel',
      'node__rev',
      'node__session',
      );

    foreach ($tables as $table) {
      $c = mcms::db()->fetch("SELECT COUNT(*) FROM `{$table}`");
      $t = new TableInfo($table);
      $this->assertTrue($t->exists());
    }
  }
}
