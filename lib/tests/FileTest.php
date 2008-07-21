<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) .'/../bootstrap.php');

class FileTest extends PHPUnit_Framework_TestCase
{
  // Сырые данные в том виде, в котором они приходят от браузера.
  private function getRawData()
  {
    return array (
      'node_content_files' => array (
        'name' => array (
          '__bebop' => array (
            0 => 'RiceeeyTweak.sh',
            1 => 'sql2.gz',
            2 => '',
          ),
        ),
        'type' => array (
          '__bebop' => array (
            0 => 'application/octet-stream',
            1 => 'application/x-gzip',
            2 => '',
          ),
        ),
        'tmp_name' => array (
          '__bebop' => array (
            0 => '/var/tmp/php5BTNCd',
            1 => '/var/tmp/phpxXXPCw',
            2 => '',
          ),
        ),
        'error' => array (
          '__bebop' => array (
            0 => 0,
            1 => 0,
            2 => 4,
          ),
        ),
        'size' => array (
          '__bebop' => array (
            0 => 5154,
            1 => 5906,
            2 => 0,
          ),
        ),
      ),
    );
  }

  // Данные, которые должны вернуться из RequestContext::getFiles().
  private function getExpectedResult()
  {
    return array (
      'node_content_files' => array (
        '__bebop0' => array (
          'name' => 'RiceeeyTweak.sh',
          'type' => 'application/octet-stream',
          'tmp_name' => '/var/tmp/php5BTNCd',
          'error' => 0,
          'size' => 5154,
        ),
        '__bebop1' => array (
          'name' => 'sql2.gz',
          'type' => 'application/x-gzip',
          'tmp_name' => '/var/tmp/phpxXXPCw',
          'error' => 0,
          'size' => 5906,
        ),
        '__bebop2' => array (
          'name' => '',
          'type' => '',
          'tmp_name' => '',
          'error' => 4,
          'size' => 0,
        ),
      ),
    );
  }

  // Проверка работы RequestContext::getFiles().
  // FIXME: отключено, т.к. формат изменился.
  public function testMissingArgs()
  {
    /*
    $data = array();
    $_FILES = $this->getRawData();

    RequestContext::getFiles($data);

    mcms::debug($this->getExpectedResult(), $data);

    $this->assertEquals($this->getExpectedResult(), $data);
    */
  }
}
