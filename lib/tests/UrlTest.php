<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) .'/../bootstrap.php');

class UrlTest extends PHPUnit_Framework_TestCase
{
  public function testDummy()
  {
    $this->assertNotEquals('localhost', l('/'));
  }

  public function testRoot()
  {
    $good = 'http://localhost/';

    $this->assertEquals($good, bebop_combine_url(array()));
    $this->assertEquals($good, l('/'));
  }

  public function testRootWithArg()
  {
    $good = 'http://localhost/?arg=ok';

    $this->assertEquals($good, l('/?arg=ok'));
  }

  public function testIndex()
  {
    $this->assertEquals('http://localhost/', l('/index.php'));
  }

  public function testIndexWithArgs()
  {
    $this->assertEquals('http://localhost/?arg=ok', l('/index.php?arg=ok'));
  }

  public function testRecombine()
  {
    $src = 'themes/admin/img/openid.png';
    $dst = bebop_combine_url(bebop_split_url($src));

    $this->assertEquals('http://localhost/themes/admin/img/openid.png', $dst);
  }

  public function testLShort()
  {
    $src = 'themes/admin/img/openid.png';
    $dst = l($src);

    $this->assertEquals('http://localhost/themes/admin/img/openid.png', $dst);
  }

  public function testLText()
  {
    $tmp = l('themes/admin/img/openid.png', 'OpenID');
    $this->assertEquals('<a href=\'http://localhost/themes/admin/img/openid.png\'>OpenID</a>', $tmp);
  }

  public function testLTextTitle()
  {
    $tmp = l('themes/admin/img/openid.png', 'OpenID', array('title' => '<>'));
    $this->assertEquals('<a title=\'&lt;&gt;\' href=\'http://localhost/themes/admin/img/openid.png\'>OpenID</a>', $tmp);
  }

  /**
   * @expectedException RuntimeException
   */
  public function testLEmpty()
  {
    $tmp = l(null);
  }
}
