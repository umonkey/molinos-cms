<?php

require dirname(__FILE__) . '/../lib/modules/core/class.spyc.php';

if ($argc < 2)
  die("Usage: ini2yml.php input\n");

$ini = parse_ini_file($argv[1], true);
$yml = Spyc::YAMLDump($ini);

print $yml;
