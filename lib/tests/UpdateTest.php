<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) .'/../bootstrap.php');

class UpdateTest extends PHPUnit_Framework_TestCase
{
  public function testUpdateError()
  {
    $content = mcms::version(mcms::VERSION_AVAILABLE_URL);
    $this->assertNotEquals('', $content);
  }
}
