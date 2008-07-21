<?php

class TableInfoTest extends PHPUnit_Framework_TestCase
{
  const tbl = 'xyz_abc';

  public function testNotExists()
  {
    $t = new TableInfo(self::tbl);
    $this->assertFalse($t->exists());
  }

  public function testCreate()
  {
    $t = new TableInfo(self::tbl);
    $t->columnSet('id', array(
      'type' => 'integer',
      'required' => 1,
      'key' => 'pri',
      'autoincrement' => 1,
      ));

    $sql = $t->getSql();
    $this->assertEquals('CREATE TABLE `xyz_abc` '
      .'(`id` integer NOT NULL PRIMARY KEY)',
      $sql);

    $t->commit();

    $t = new TableInfo(self::tbl);
    $this->assertTrue($t->exists());
  }

  public function testAddColumn()
  {
    $t = new TableInfo(self::tbl);
    $t->columnSet('value', array(
      'type' => 'text',
      ));
    $t->commit();

    $t = new TableInfo(self::tbl);
    $this->assertEquals(2, $t->columnCount());
  }

  public function testDropColumn()
  {
    $t = new TableInfo(self::tbl);
    $t->columnDel('value');
    $this->assertEquals(1, $t->columnCount());
  }

  public function testDropTable()
  {
    $t = new TableInfo(self::tbl);
    $t->delete();
  }

  /**
   * @expectedException RuntimeException
   */
  public function testDropTableFail()
  {
    $t = new TableInfo(self::tbl);
    $t->delete();
  }
}
