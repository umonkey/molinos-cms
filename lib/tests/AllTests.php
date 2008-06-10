#!/usr/local/bin/php -f
<?php
// Based on Alexei Zakhlestine's MySQL Query Builder,
// http://code.google.com/p/mysql-query-builder/source/browse/trunk/tests/AllTests.php

define('MCMS_PATH', '/sites/testsite/');

if (!defined('PHPUnit_MAIN_METHOD'))
  define('PHPUnit_MAIN_METHOD', 'AllTests::main');

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require 'ConstTest.php';
require 'UrlTest.php';
require 'HtmlTest.php';
require 'FileTest.php';
require 'SchemaTest.php';

class AllTests
{
  public static function main()
  {
    PHPUnit_TextUI_TestRunner::run(self::suite());
  }

  public static function suite()
  {
    $suite = new PHPUnit_Framework_TestSuite('PHPUnit Framework');

    $suite->addTestSuite('ConstTest');
    $suite->addTestSuite('UrlTest');
    $suite->addTestSuite('HtmlTest');
    $suite->addTestSuite('FileTest');
    $suite->addTestSuite('SchemaTest');

    return $suite;
  }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main')
  AllTests::main();
