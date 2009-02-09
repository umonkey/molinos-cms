<?php

class PdoModuleTests extends PHPUnit_Framework_TestCase
{
  const tbl = 'xyz_abc';

  public function testGetDbType()
  {
    $ctx = get_test_context();
    $this->assertEquals('SQLite', $ctx->db->getDbType());
  }

  public function testGetDbName()
  {
    $this->assertEquals('conf/test.db', get_test_context()->db->getDbName());
  }

  public function testGetConfig()
  {
    $this->assertTrue(get_test_context()->db->prepare("SELECT 1") instanceof PDOStatement);
  }

  public function testExecOk()
  {
    $want = array(array(1 => '1'));
    $this->assertEquals($want, get_test_context()->db->getResults("SELECT 1"));
  }

  /**
   * @expectedException TableNotFoundException
   */
  public function testExecFailNoTable()
  {
    get_test_context()->db->exec("SELECT * FROM this_table_never_exists");
  }

  public function testGetResultsKV()
  {
    $a = array(1 => 2);
    $b = get_test_context()->db->getResultsKV('a', 'b', 'SELECT 1 AS a, 2 AS b');
    $this->assertEquals($a, $b);
  }

  public function testGetResultsK()
  {
    $a = array (
      1 => array (
        'a' => '1',
        'b' => '2',
        ),
      );
    $b = get_test_context()->db->getResultsK('a', 'SELECT 1 AS a, 2 AS b');
    $this->assertEquals($a, $b);
  }

  public function testGetResultsV()
  {
    $a = array(0 => '2');
    $b = get_test_context()->db->getResultsV('b', 'SELECT 1 AS a, 2 AS b');
    $this->assertEquals($a, $b);
  }

  public function testGetResult()
  {
    $db = get_test_context()->db;

    $a = array('a' => '1', 'b' => '2');
    $b = $db->getResult('SELECT 1 AS a, 2 AS b');
    $this->assertEquals($a, $b);

    $a = 1;
    $b = $db->getResult('SELECT 1 AS a');
    $this->assertEquals($a, $b);
  }

  public function testFetchOk()
  {
    $this->assertEquals(123, get_test_context()->db->fetch("SELECT 123"));
  }

  public function testFetchFail()
  {
    $this->assertNotEquals(456, get_test_context()->db->fetch("SELECT 123"));
  }

  // Таблица нужна, в основном, чтобы протестировать транзакции.
  public function testCreateTable()
  {
    $db = get_test_context()->db;
    $db->exec('CREATE TABLE tmp123(a int)');
    $db->exec('SELECT * FROM tmp123');
  }

  public function testCommit()
  {
    $db = get_test_context()->db;
    $db->beginTransaction();
    $db->exec('INSERT INTO tmp123 VALUES (?)', array(123));
    $db->commit();

    $this->assertEquals(123, $db->fetch('SELECT * FROM tmp123'));

    $db->beginTransaction();
    $db->exec('DELETE FROM tmp123');
    $db->commit();

    $this->assertEquals(null, $db->fetch('SELECT * FROM tmp123'));
  }

  public function testRollback()
  {
    $db = get_test_context()->db;

    $db->exec("CREATE TABLE `tmp1234` (`value` INTEGER)");
    $db->beginTransaction();
    $db->exec('INSERT INTO `tmp1234` VALUES (?)', array(123));
    $db->rollback();

    $this->assertEquals(null, $db->fetch('SELECT * FROM `tmp1234`'));
    $db->exec("DROP TABLE `tmp1234`");
  }

  /**
   * @expectedException TableNotFoundException
   */
  public function testDropTable()
  {
    $db = get_test_context()->db;
    $db->exec('DROP TABLE tmp123');
    $db->fetch('SELECT * FROM tmp123');
  }

  public function hasOrderedUpdates()
  {
    $this->assertFalse(get_test_context()->db->hasOrderedUpdates());
  }

  public function testConnect()
  {
    $tmp = get_test_context()->db->getResult("SELECT 1");
    $this->assertEquals(1, $tmp);
  }

  public function testTableInfoCreate()
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

  public function testTableInfoAddColumn()
  {
    $t = new TableInfo(self::tbl);
    $t->columnSet('value', array(
      'type' => 'text',
      ));
    $t->commit();

    $t = new TableInfo(self::tbl);
    $this->assertEquals(2, $t->columnCount());
  }

  public function testTableInfoDropColumn()
  {
    $t = new TableInfo(self::tbl);
    $t->columnDel('value');
    $this->assertEquals(1, $t->columnCount());
  }

  public function testTableInfoDropTable()
  {
    $t = new TableInfo(self::tbl);
    $t->delete();
  }

  /**
   * @expectedException RuntimeException
   */
  public function testTableInfoDropTableFail()
  {
    $t = new TableInfo(self::tbl);
    $t->delete();
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
    return get_test_context()->db->getResult("SELECT `sql` FROM `sqlite_master` WHERE `tbl_name` = 'test' AND `type` = 'table'");
  }

  public function testInternalTables()
  {
    $tables = array(
      'node',
      'node__access',
      'node__rel',
      );

    $ctx = get_test_context();

    foreach ($tables as $table) {
      $c = $ctx->db->fetch("SELECT COUNT(*) FROM `{$table}`");
      $t = new TableInfo($table);
      $this->assertTrue($t->exists());
    }
  }
}
