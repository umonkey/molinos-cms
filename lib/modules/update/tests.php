<?php

class UpdateModuleTests extends PHPUnit_Framework_TestCase
{
  public function testUpdateError()
  {
    $content = mcms::version(mcms::VERSION_AVAILABLE_URL);
    $this->assertNotEquals('', $content);
  }
}
