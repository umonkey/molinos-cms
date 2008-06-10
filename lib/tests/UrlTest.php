<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) .'/../bootstrap.php');

class UrlTest extends PHPUnit_Framework_TestCase
{
  private function clean($clean = true)
  {
    BebopConfig::getInstance()->set('cleanurls', $clean);
  }

  private function dirty()
  {
    self::clean(false);
  }

  public function testConst()
  {
    $this->assertEquals('/sites/testsite/', MCMS_PATH);
  }

  public function testDummy()
  {
    $this->assertNotEquals('localhost', l('/'));
  }

  public function testRoot()
  {
    self::dirty();

    $good = 'http://localhost'. MCMS_PATH;

    $this->assertEquals($good, bebop_combine_url(array()));
    $this->assertEquals($good, l('/'));
  }

  public function testRootWithArg()
  {
    $good = 'http://localhost'. MCMS_PATH .'?arg=ok';

    $this->assertEquals($good, l('/?arg=ok'));
  }

  public function testIndex()
  {
    $this->assertEquals('http://localhost'. MCMS_PATH, l('/index.php'));
  }

  public function testIndexWithArgs()
  {
    $this->assertEquals('http://localhost'. MCMS_PATH .'?arg=ok', l('/index.php?arg=ok'));
  }

  public function testAdmin()
  {
    $this->assertEquals('http://localhost'. MCMS_PATH .'?q=admin', l('/admin/'));
  }

  public function testRecombine()
  {
    $src = 'themes/admin/img/openid.png';
    $dst = bebop_combine_url(bebop_split_url($src));

    $this->assertEquals('http://localhost'. MCMS_PATH .'themes/admin/img/openid.png', $dst);
  }

  public function testLShort()
  {
    $src = 'themes/admin/img/openid.png';
    $dst = l($src);

    $this->assertEquals('http://localhost'. MCMS_PATH .'themes/admin/img/openid.png', $dst);
  }

  public function testLText()
  {
    $tmp = l('themes/admin/img/openid.png', 'OpenID');
    $this->assertEquals('<a href=\'http://localhost'. MCMS_PATH .'themes/admin/img/openid.png\'>OpenID</a>', $tmp);
  }

  public function testLTextTitle()
  {
    $tmp = l('themes/admin/img/openid.png', 'OpenID', array('title' => '<>'));
    $this->assertEquals('<a title=\'&lt;&gt;\' href=\'http://localhost'. MCMS_PATH .'themes/admin/img/openid.png\'>OpenID</a>', $tmp);
  }

  public function testCleanRoot()
  {
    self::clean();
    $this->assertEquals('http://localhost/sites/testsite/', l('/'));
  }

  public function testCleanIndex()
  {
    self::clean();
    $this->assertEquals('http://localhost/sites/testsite/', l('index.php'));
  }

  public function testCleanAdmin()
  {
    self::clean();

    $this->assertEquals('http://localhost/sites/testsite/admin/?test=ok', l('?q=admin&test=ok'));
    $this->assertEquals('http://localhost/sites/testsite/admin/?test=ok', l('/admin/?test=ok'));
  }

  /**
   * @expectedException RuntimeException
   */
  public function testLEmpty()
  {
    $tmp = l(null);
  }
}
