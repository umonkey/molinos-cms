<?php

class PdoModuleTests extends PHPUnit_Framework_TestCase
{
  const tbl = 'xyz_abc';

  public function testInit()
  {
    copy(MCMS_ROOT.'/conf/default.ini', MCMS_ROOT.'/conf/default_backup.ini');
    $config = BebopConfig::getInstance();
    $config->set('default','sqlite::memory:','db');
    PDO_Singleton::getInstance('default', true);
  }

  public function testGetDbType()
  {
    $this->assertEquals('SQLite', mcms::db()->getDbType());
  }

  public function testGetDbName()
  {
    $this->assertEquals(':memory:', mcms::db()->getDbName());
  }

  public function testGetConfig()
  {
    $this->assertTrue(mcms::db()->prepare("SELECT 1") instanceof PDOStatement);
  }

  public function testExecOk()
  {
    $want = array(array(1 => '1'));
    $this->assertEquals($want, mcms::db()->getResults("SELECT 1"));
  }

  /**
   * @expectedException McmsPDOException
   */
  public function testExecFailSyntax()
  {
    mcms::db()->exec("SELECT oops.");
  }

  /**
   * @expectedException TableNotFoundException
   */
  public function testExecFailNoTable()
  {
    mcms::db()->exec("SELECT * FROM this_table_never_exists");
  }

  public function testGetResultsKV()
  {
    $a = array(1 => 2);
    $b = mcms::db()->getResultsKV('a', 'b', 'SELECT 1 AS a, 2 AS b');
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
    $b = mcms::db()->getResultsK('a', 'SELECT 1 AS a, 2 AS b');
    $this->assertEquals($a, $b);
  }

  public function testGetResultsV()
  {
    $a = array(0 => '2');
    $b = mcms::db()->getResultsV('b', 'SELECT 1 AS a, 2 AS b');
    $this->assertEquals($a, $b);
  }

  public function testGetResult()
  {
    $a = array('a' => '1', 'b' => '2');
    $b = mcms::db()->getResult('SELECT 1 AS a, 2 AS b');
    $this->assertEquals($a, $b);

    $a = 1;
    $b = mcms::db()->getResult('SELECT 1 AS a');
    $this->assertEquals($a, $b);
  }

  public function testFetchOk()
  {
    $this->assertEquals(123, mcms::db()->fetch("SELECT 123"));
  }

  public function testFetchFail()
  {
    $this->assertNotEquals(456, mcms::db()->fetch("SELECT 123"));
  }

  public function testGetLog()
  {
    // NULL должен вернуться потому, что лог надо запрашивать явно, ?profile=1.
    $this->assertEquals(null, mcms::db()->getLog());
  }

  public function testGetLogSize()
  {
    $this->assertEquals(0, mcms::db()->getLogSize());
  }

  // Таблица нужна, в основном, чтобы протестировать транзакции.
  public function testCreateTable()
  {
    mcms::db()->exec('CREATE TABLE tmp123(a int)');
    mcms::db()->exec('SELECT * FROM tmp123');
  }

  public function testCommit()
  {
    mcms::db()->beginTransaction();
    mcms::db()->exec('INSERT INTO tmp123 VALUES (?)', array(123));
    mcms::db()->commit();

    $this->assertEquals(123, mcms::db()->fetch('SELECT * FROM tmp123'));

    mcms::db()->exec('DELETE FROM tmp123');
    $this->assertEquals(null, mcms::db()->fetch('SELECT * FROM tmp123'));
  }

  public function testRollback()
  {
    mcms::db()->beginTransaction();
    mcms::db()->exec('INSERT INTO tmp123 VALUES (?)', array(123));
    mcms::db()->rollback();

    $this->assertEquals(null, mcms::db()->fetch('SELECT * FROM tmp123'));
  }

  /**
   * @expectedException TableNotFoundException
   */
  public function testDropTable()
  {
    mcms::db()->exec('DROP TABLE tmp123');
    mcms::db()->fetch('SELECT * FROM tmp123');
  }

  public function hasOrderedUpdates()
  {
    $this->assertFalse(mcms::db()->hasOrderedUpdates());
  }

  public function testCreateNodeTable()
  {
    $t = new TableInfo('node');
    $this->assertFalse($t->exists());

    $c = mcms::db()->fetch('SELECT COUNT(*) FROM node');
    $this->assertEquals(0, $c);

    $t = new TableInfo('node');
    $this->assertTrue($t->exists());
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
    return mcms::db()->getResult("SELECT `sql` FROM `sqlite_master` WHERE `tbl_name` = 'test' AND `type` = 'table'");
  }

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

  public function testRestore()
  {
    copy(MCMS_ROOT.'/conf/default_backup.ini', MCMS_ROOT.'/conf/default.ini');
    unlink(MCMS_ROOT.'/conf/default_backup.ini');
    $config = BebopConfig::getInstance();
    $config->set('default','sqlite:conf/default.db','db');
    PDO_Singleton::getInstance('default', true);
  }
}
