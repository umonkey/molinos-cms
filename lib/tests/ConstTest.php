<?php

class ConstTest extends PHPUnit_Framework_TestCase
{
  public function testRoot()
  {
    $this->assertEquals(dirname(dirname(dirname(__FILE__))), MCMS_ROOT);
  }

  public function testCwd()
  {
    $this->assertEquals(getcwd(), MCMS_ROOT);
  }

  public function testBootstrap()
  {
    if (is_readable($path = dirname(__FILE__) .'/../bootstrap.php'))
      require_once($path);
    else
      throw new Exception('Unable to open bootstrap.');

    if (file_exists($f = 'conf/default.ini'))
      unlink($f);

    $ini = file_get_contents('conf/default.ini.dist');
    $ini = str_replace('sqlite:conf/default.db', 'sqlite::memory:', $ini);
    file_put_contents($f, $ini);
  }

  public function testConfig()
  {
    if (':memory:' != mcms::db()->getDbName())
      throw new Exception('SQLite db is not in memory.');
  }
}
