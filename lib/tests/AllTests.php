#!/usr/local/bin/php -f
<?php
// Based on Alexei Zakhlestine's MySQL Query Builder,
// http://code.google.com/p/mysql-query-builder/source/browse/trunk/tests/AllTests.php

if (!defined('PHPUnit_MAIN_METHOD'))
  define('PHPUnit_MAIN_METHOD', 'AllTests::main');

require_once dirname(__FILE__) .'/../bootstrap.php';
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

class AllTests
{
  public static function main()
  {
    PHPUnit_TextUI_TestRunner::run(self::suite());
  }

  public static function suite()
  {
    $suite = new PHPUnit_Framework_TestSuite('PHPUnit Framework');
    $root = dirname(__FILE__) .'/../modules/';

    foreach (glob($root .'*'.'/tests.php') as $file) {
      if (is_readable($file)) {
        include $file;
        $class = ucfirst(basename(dirname($file))) .'ModuleTests';
        $suite->addTestSuite($class);
      }
    }

    foreach (glob($root .'*/class.*.test.php') as $file) {
      if (is_readable($file)) {
        if (preg_match('@^class\.([^.]+)\.test\.php$@', basename($file), $m)) {
          $class = ucfirst($m[1]) .'ClassTest';

          include $file;
          $suite->addTestSuite($class);
        }
      }
    }

    return $suite;
  }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main')
  AllTests::main();
