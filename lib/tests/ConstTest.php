<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) .'/../bootstrap.php');

class ConstTest extends PHPUnit_Framework_TestCase
{
  public function testRoot()
  {
    $this->assertEquals(MCMS_ROOT, dirname(dirname(dirname(__FILE__))));
  }

  public function testPath()
  {
    $this->assertEquals(MCMS_PATH, '/');
  }

  public function testCwd()
  {
    $this->assertEquals(getcwd(), MCMS_ROOT);
  }
}
