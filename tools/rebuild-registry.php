<?php

$rootPath = dirname(dirname(__FILE__));

require $rootPath . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'modules'
  . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'class.os.php';

require os::path($rootPath, 'lib', 'modules', 'core', 'class.ini.php');

$search = os::path($rootPath, 'lib', 'modules', '*', 'module.ini');

foreach (glob($search) as $iniFileName) {
  $moduleName = basename(dirname($iniFileName));

  if (!empty($argv[1]) and $argv[1] != $moduleName)
    continue;

  printf("processing %s...\n", $moduleName);

  $ini = ini::read($iniFileName);
  $path = dirname($iniFileName);

  foreach ($ini as $k => $v)
    if (is_array($v))
      unset($ini[$k]);

  foreach (glob(os::path($path, '*.php')) as $fileName) {
    $baseName = basename($fileName);

    if (0 === strpos($baseName, 'test'))
      continue;
    elseif ('.test.php' == substr($baseName, -9))
      continue;

    $source = strtolower(file_get_contents($fileName));

    if (preg_match('@^\s*(?:abstract\s+)?class\s+([a-z0-9_]+)(\s+extends\s+([^\s]+))*(\s+implements\s+([^\n\r]+))*@m', $source, $m)) {
      $ini['classes'][$m[1]] = $baseName;
      printf(" + %s\n", $className = $m[1]);

      if (preg_match_all('#(?:@mcms_message\s+)([a-z0-9.]+)(?:[^{]*public\s+static\s+function\s+)([^(]+)#s', $source, $m)) {
        foreach ($m[1] as $idx => $message) {
          $method = $m[2][$idx];
          $ini['messages'][$message][] = $className . '::' . $method;
          printf("   @%s = %s::%s()\n", $message, $className, $method);
        }
      }
    }

    elseif (preg_match('@^\s*interface\s+([a-z0-9_]+)@m', $source, $m)) {
      $ini['classes'][$m[1]] = $baseName;
      printf(" + %s\n", $m[1]);
    }
  }

  ksort($ini['classes']);

  ini::write($iniFileName, $ini);
}

foreach (glob(os::path('sites', '*', '.registry.php')) as $reg) {
  printf("- %s\n", $reg);
  unlink($reg);
}
