<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) .'/../bootstrap.php');

class UrlTest extends PHPUnit_Framework_TestCase
{
  public function testBulk()
  {
    $urls = array(
      'http://www.google.com/',
      'https://user:password@gmail.com/inbox/#label',
      'attachment/123',
      'att.php?q=123',
      '?q=nodeapi.rpc&action=stop',
      );

    foreach ($urls as $k => $v) {
      $link = strval(new url($v));
      $this->assertEquals($v, $link);
    }
  }
}
