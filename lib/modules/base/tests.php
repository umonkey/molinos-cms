<?php

class BaseModuleTests extends PHPUnit_Framework_TestCase
{
  public function testRoot()
  {
    $this->assertEquals(dirname(dirname(dirname(dirname(__FILE__)))), MCMS_ROOT);
  }

  public function testCwd()
  {
    $this->assertEquals(getcwd(), MCMS_ROOT);
  }

  public function testConfig()
  {
    $ctx = get_test_context();

    if ('conf/test.db' != $ctx->db->getDbName())
      throw new Exception('Bad database.');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testMissingArgsHTML()
  {
    $tmp = html::em();
    $this->assertEquals($tmp, '123');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testEmptyHTML()
  {
    $tmp = html::em(null);
  }

  public function testSpanHTML()
  {
    $tmp = html::em('span');
    $this->assertEquals('<span></span>', $tmp);
  }
}
