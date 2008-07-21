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
      '?fid=123&q=attachment.rpc',
      '?action=stop&q=nodeapi.rpc',
      );

    foreach ($urls as $k => $v) {
      if (is_numeric($k))
        $link = strval(new url($v));
      else
        $link = strval(new url($k));
      $this->assertEquals($v, $link);
    }
  }

  public function testCurrent()
  {
    $url1 = 'test.rpc?destination=CURRENT';
    $url2 = l($url1);

    $this->assertNotEquals($url1, $url2);
  }
}
