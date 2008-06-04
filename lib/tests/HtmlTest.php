<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) .'/../bootstrap.php');

class HtmlTest extends PHPUnit_Framework_TestCase
{
  /**
   * @expectedException InvalidArgumentException
   */
  public function testMissingArgs()
  {
    $tmp = mcms::html();
    $this->assertEquals($tmp, '123');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testEmpty()
  {
    $tmp = mcms::html(null);
  }

  public function testSpan()
  {
    $tmp = mcms::html('span');
    $this->assertEquals('<span></span>', $tmp);
  }
}
