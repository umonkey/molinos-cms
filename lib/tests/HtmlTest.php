<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) .'/../bootstrap.php');

class HtmlTest extends PHPUnit_Framework_TestCase
{
  public function testParseHTML()
  {
    $html = '<script type="text/javascript" language="javascript" '
      .'src="themes/test.js">hello</script>';

    $good = array (
      'type' => 'text/javascript',
      'language' => 'javascript',
      'src' => 'themes/test.js',
      );

    $this->assertEquals($good, mcms::parse_html($html));
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testMissingArgs()
  {
    $tmp = mcms::html();
    $this->assertEquals($tmp, '123');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testEmpty()
  {
    $tmp = mcms::html(null);
  }

  public function testSpan()
  {
    $tmp = mcms::html('span');
    $this->assertEquals('<span></span>', $tmp);
  }
}
