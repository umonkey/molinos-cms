<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) .'/../bootstrap.php');

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
}
