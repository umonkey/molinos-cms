<?php

class HtmlTests extends PHPUnit_Framework_TestCase
{
  /**
   * @expectedException InvalidArgumentException
   */
  public function testEmNoArgs()
  {
    html::em();
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testEmTooManyArgs()
  {
    html::em(1, 2, 3, 4);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testEmEmptyName()
  {
    html::em(null);
  }

  public function testEmSimple()
  {
    $html = html::em('br');
    $this->assertEquals('<br />', $html);
  }

  public function testEmWithContent()
  {
    $html = html::em('p', 'hello');
    $this->assertEquals('<p>hello</p>', $html);
  }

  public function testEmWithAttributes()
  {
    $html = html::em('br', array(
      'class' => 'hidden',
      ));
    $this->assertEquals('<br class=\'hidden\' />', $html);
  }

  public function testEmTdThEmpty()
  {
    $html = html::em('td');
    $this->assertEquals('<td>&nbsp;</td>', $html);

    $html = html::em('th');
    $this->assertEquals('<th>&nbsp;</th>', $html);
  }

  public function testEmptyDiv()
  {
    $html = html::em('div');
    $this->assertEquals('<div></div>', $html);

    $html = html::em('script');
    $this->assertEquals('<script></script>', $html);

    $html = html::em('textarea');
    $this->assertEquals('<textarea></textarea>', $html);

    $html = html::em('span');
    $this->assertEquals('<span></span>', $html);

    $html = html::em('base');
    $this->assertEquals('<base></base>', $html);

    $html = html::em('a');
    $this->assertEquals('<a></a>', $html);
  }

  public function testAttrs()
  {
    $a = html::attrs(array(
      'class' => 'test',
      'title' => 'hello',
      'width' => 0,
      'checked' => true,
      ));

    $this->assertEquals(" class='test' title='hello' checked='yes'", $a);
  }

  public function testNonUniqueClasses()
  {
    $a = html::attrs(array(
      'class' => 'test ok test ok xyz',
      ));
    $this->assertEquals(" class='test ok xyz'", $a);
  }

  public function testSimpleList()
  {
    $html = html::simpleList(array('one', 'two'));
    $this->assertEquals('<li>one</li><li>two</li>', $html);
  }

  public function testSimpleOptions()
  {
    $html = html::simpleOptions(array(
      'a' => 'first',
      'b' => 'second',
      ));
    $this->assertEquals("<option value='a'>first</option><option value='b'>second</option>", $html);
  }

  public function testCdata()
  {
    $this->assertEquals(null, html::cdata(null));
    $this->assertEquals(null, html::cdata(0));
    $this->assertEquals(null, html::cdata(''));
    $this->assertEquals(null, html::cdata(array(123)));
    $this->assertEquals(null, html::cdata(new stdClass()));
    $this->assertEquals('test', html::cdata('test'));
    $this->assertEquals('<![CDATA[&nbsp;]]>', html::cdata('&nbsp;'));
  }

  public function formatExtras()
  {
    $html = html::formatExtras(array(
      array(
        'style',
        'test.css'
        ),
      array(
        'script',
        'test.js',
        ),
      ));
    $this->assertEquals("<link rel='stylesheet' type='text/css' href='test.css' /><script type='text/javascript' src='test.js'></script>", $html);
  }
}
