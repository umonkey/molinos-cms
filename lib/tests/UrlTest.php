<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) .'/../bootstrap.php');

class UrlTest extends PHPUnit_Framework_TestCase
{
  public function testDummy()
  {
    $this->assertNotEquals(l('/'), 'localhost');
  }

  public function testRecombine()
  {
    $src = 'themes/admin/img/openid.png';
    $dst = bebop_combine_url(bebop_split_url($src));

    $this->assertEquals($dst, 'http://localhost/themes/admin/img/openid.png');
  }

  public function testLShort()
  {
    $src = 'themes/admin/img/openid.png';
    $dst = l($src);

    $this->assertEquals($dst, 'http://localhost/themes/admin/img/openid.png');
  }

  public function testLText()
  {
    $tmp = l('themes/admin/img/openid.png', 'OpenID');
    $this->assertEquals($tmp, '<a href=\'http://localhost/themes/admin/img/openid.png\'>OpenID</a>');
  }

  public function testLTextTitle()
  {
    $tmp = l('themes/admin/img/openid.png', 'OpenID', array('title' => '<>'));
    $this->assertEquals($tmp, '<a title=\'&lt;&gt;\' href=\'http://localhost/themes/admin/img/openid.png\'>OpenID</a>');
  }

  /**
   * @expectedException RuntimeException
   */
  public function testLEmpty()
  {
    $tmp = l(null);
  }
}
