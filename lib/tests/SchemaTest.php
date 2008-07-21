<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) .'/../bootstrap.php');

class SchemaTest extends PHPUnit_Framework_TestCase
{
  public function testInitDB()
  {
    /*
    copy('conf/example.db', $to = 'conf/test.db');
    $this->assertTrue(file_exists($to));
    */
  }

  public function testSetDSN()
  {
    $config = BebopConfig::getInstance();
    $config->set('default', $path = 'sqlite:conf/test.db', 'db');

    $this->assertEquals($path, $config->db_default);
  }

  public function testConnect()
  {
    $tmp = mcms::db()->getResult("SELECT COUNT(*) FROM `node`");
    $this->assertEquals(0, $tmp);
  }

  public function testCreateTable()
  {
    $t = new TableInfo('test');

    $this->assertFalse($t->exists());

    $t->columnSet('a', array(
      'type' => 'int',
      'required' => true,
      'key' => 'pri',
      ));
    $t->commit();

    // Проверим, создаётся ли таблица.
    $this->assertEquals('CREATE TABLE `test` (`a` int NOT NULL PRIMARY KEY)', $this->getTableDef());
  }

  // Проверяем, успешно ли добавляется одна колонка.
  public function testAddOneColumn()
  {
    $t = new TableInfo('test');

    $this->assertTrue($t->exists());

    $t->columnSet('b', array(
      'type' => 'int',
      'required' => true,
      'key' => 'mul',
      ));
    $t->commit();
  }

  public function testRemoveDB()
  {
    /*
    unlink($to = 'conf/test.db');
    $this->assertTrue(!file_exists($to));
    */
  }

  private function getTableDef()
  {
    return mcms::db()->getResult("SELECT `sql` FROM `sqlite_master` WHERE `tbl_name` = 'test' AND `type` = 'table'");
  }
}
