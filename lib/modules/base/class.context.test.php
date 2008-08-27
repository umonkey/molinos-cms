<?php

class ContextTests extends PHPUnit_Framework_TestCase
{
  public function testDefaultMakeNotEmpty()
  {
    $ctx = new Context();
    $this->assertEquals(true, empty($ctx->get));
  }

  public function testMakeWithEmptyArray()
  {
    $ctx = new Context(array());
    $this->assertEquals(false, empty($ctx));
  }

  public function testMakeFixedUrl()
  {
    $url = 'http://www.molinos-cms.ru/?test=ok';

    $ctx = new Context(array(
      'url' => $url,
      ));

    $this->assertEquals(true, $ctx->url() instanceof url);
    $this->assertEquals($url, strval($ctx->url()));
    $this->assertEquals($url, $ctx->url()->getAbsolute());
  }

  public function testGetArgs()
  {
    $url = 'http://www.molinos-cms.ru/?a=1&b=0';

    $ctx = new Context(array(
      'url' => $url,
      ));

    $this->assertEquals('1', $ctx->get('a'));
    $this->assertEquals('0', $ctx->get('b'));
  }

  public function testEmptyFolder()
  {
    $ctx = new Context(array(
      'url' => '/mcms/node/123',
      ));
    $this->assertEquals('', $ctx->folder());
    $this->assertEquals('mcms/node/123', $ctx->query());
  }

  public function testNonEmptyFolder()
  {
    $ctx = new Context(array(
      'url' => '/mcms/node/123',
      'folder' => 'mcms',
      ));
    $this->assertEquals('/mcms', $ctx->folder());
    $this->assertEquals('node/123', $ctx->query());
  }

  /**
   * @expectedException RuntimeException
   */
  public function testReadOnlyUrl()
  {
    $url = 'http://www.molinos-cms.ru/?a=1&b=0';

    $ctx = new Context(array(
      'url' => $url,
      ));

    $ctx->url()->setarg('c', '2');

    mcms::debug($ctx->url());
  }
}
