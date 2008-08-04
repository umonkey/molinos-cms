#!/usr/local/bin/php -f
<?php
// Based on Alexei Zakhlestine's MySQL Query Builder,
// http://code.google.com/p/mysql-query-builder/source/browse/trunk/tests/AllTests.php

if (!defined('PHPUnit_MAIN_METHOD'))
  define('PHPUnit_MAIN_METHOD', 'AllTests::main');

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once dirname(__FILE__) .'/../bootstrap.php';

class AllTests
{
  public static function main()
  {
    mcms::flush(mcms::FLUSH_NOW);
    PHPUnit_TextUI_TestRunner::run(self::suite());
  }

  public static function suite()
  {
    ini_set('display_errors', 1);

    $suite = new PHPUnit_Framework_TestSuite('PHPUnit Framework');

    $mask = realpath(dirname(__FILE__) .'/../modules') .'/*/tests.php';

    foreach (glob($mask) as $file) {
      $module = basename(dirname($file));
      $class = ucfirst($module) .'ModuleTests';

      require_once $file;
      if (class_exists($class, false))
        $suite->addTestSuite($class);
      else
        die("Class {$class} not found in {$file}.\n");
    }

    ini_set('error_log', dirname(__FILE__) .'/AllTests.log');
    ini_set('display_errors', 0);

    return $suite;
  }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main')
  AllTests::main();
