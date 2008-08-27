<?php

class ContextTests extends PHPUnit_Framework_TestCase
{
  public function testDefaultMakeNotEmpty()
  {
    $ctx = Context::make();
    $this->assertEquals(true, empty($ctx->get));
  }

  public function testMakeWithEmptyArray()
  {
    $ctx = Context::make(array());
    $this->assertEquals(false, empty($ctx));
  }

  public function testMakeFixedUrl()
  {
    $url = 'http://www.molinos-cms.ru/?test=ok';

    $ctx = Context::make(array(
      'url' => $url,
      ));

    $this->assertEquals(true, $ctx->url() instanceof url);
    $this->assertEquals($url, strval($ctx->url()));
    $this->assertEquals($url, $ctx->url()->getAbsolute());
  }

  public function testEmptyPath()
  {
    $url = 'http://www.molinos-cms.ru/?test=ok';

    $ctx = Context::make(array(
      'url' => $url,
      ));

    $this->assertEquals('/', $ctx->path());
  }

  public function testNonEmptyPath()
  {
    $url = 'http://www.molinos-cms.ru/test/?test=ok';

    $ctx = Context::make(array(
      'url' => $url,
      ));

    $this->assertEquals('/test/', $ctx->path());
  }

  public function testGetArgs()
  {
    $url = 'http://www.molinos-cms.ru/?a=1&b=0';

    $ctx = Context::make(array(
      'url' => $url,
      ));

    $this->assertEquals('1', $ctx->get('a'));
    $this->assertEquals('0', $ctx->get('b'));
  }

  /**
   * @expectedException RuntimeException
   */
  public function testReadOnlyUrl()
  {
    $url = 'http://www.molinos-cms.ru/?a=1&b=0';

    $ctx = Context::make(array(
      'url' => $url,
      ));

    $ctx->url()->setarg('c', '2');

    mcms::debug($ctx->url());
  }
}
