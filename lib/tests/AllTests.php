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

    $mask = realpath(dirname(__FILE__) .'/../modules')
      .'/*/{tests,class.*.test}.php';

    foreach (glob($mask, GLOB_BRACE) as $file) {
      $module = basename(dirname($file));

      if ('.test.php' == substr(basename($file), -9))
        $class = ucfirst(substr(basename($file), 6, -9)) .'Tests';
      else
        $class = ucfirst($module) .'ModuleTests';

      require_once $file;

      if (class_exists($class, false)) {
        printf("%s => %s\n", $file, $class);
        $suite->addTestSuite($class);
      } else {
        die("Class {$class} not found in {$file}.\n");
      }
    }

    ini_set('error_log', dirname(__FILE__) .'/tests.log');
    ini_set('display_errors', 1);

    return $suite;
  }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main')
  AllTests::main();
