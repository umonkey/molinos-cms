<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) .'/../bootstrap.php');

class UrlTest extends PHPUnit_Framework_TestCase
{
  private function clean($clean = true)
  {
    url::__setclean($clean);
  }

  private function dirty()
  {
    self::clean(false);
  }

  public function testConst()
  {
    $this->assertEquals(dirname(dirname(dirname(__FILE__))), MCMS_ROOT);
  }

  public function testDummy()
  {
    $this->assertNotEquals('localhost', l('/'));
  }

  public function testIsLocal()
  {
    $url = new url();
    $this->assertEquals(true, $url->islocal);
  }

  public function testRoot()
  {
    self::dirty();

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

  public function testAdmin()
  {
    self::dirty();
    $this->assertEquals('http://localhost/?q=admin', l('/admin/'));
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

  public function testCleanRoot()
  {
    self::clean();
    $this->assertEquals('http://localhost/', l('/'));
  }

  public function testCleanIndex()
  {
    self::clean();
    $this->assertEquals('http://localhost/', l('index.php'));
  }

  public function testCleanAdmin()
  {
    self::clean();

    $this->assertEquals('http://localhost/admin/?test=ok', l('?q=admin&test=ok'));
    $this->assertEquals('http://localhost/admin/?test=ok', l('/admin/?test=ok'));
  }

  /**
   * @expectedException RuntimeException
   */
  public function testLEmpty()
  {
    $tmp = l(null);
  }
}
