<?php

class UrlTests extends PHPUnit_Framework_TestCase
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
      if (is_numeric($k)) {
        $u = new url($v);
        $link = $u->string();
      } else {
        $u = new url($k);
        $link = $u->string();
      }
      $this->assertEquals($v, $link);
    }
  }

  public function testCurrent()
  {
    $url1 = '?q=test.rpc&destination=CURRENT';
    $url2 = l($url1);

    $this->assertNotEquals($url1, $url2);
  }
}
