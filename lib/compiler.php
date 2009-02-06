<?php

$local = array();
$global = array('lib' . DIRECTORY_SEPARATOR . 'bebop_functions.php');
$paths = require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classpath.inc';
$target = 'lib' . DIRECTORY_SEPARATOR . 'whole-molinos-cms.php';

function compile()
{
  global $paths, $local, $global, $target;

  if (file_exists($target))
    die("already compiled.\n");

  foreach (array_keys($paths['classes']) as $className) {
    class_exists($className);
    foreach ($local as $path)
      $global[] = $path;
    $local = array();
  }

  $content = "<?php\n";

  foreach ($global as $path) {
    printf("loading %s\n", $path);

    if (substr($tmp = file_get_contents($path), 0, 5) != '<?php')
      die("  error: no PHP signature.\n");

    $content .= ltrim(substr($tmp, 5));
  }

  printf("saving %u bytes to %s.\n", strlen($content), $target);
  file_put_contents($target, $content);
}

function autoload($className)
{
  global $paths, $local, $global;

  if (!array_key_exists($key = strtolower($className), $paths['classes']))
    die(printf("don't know where to find %s.\n", $className));

  $path = $paths['classes'][$key];
  // printf("loading %s from %s\n", $className, $paths['classes'][$key]);
  require $path;

  $local[] = $path;
}

spl_autoload_register('autoload');

compile();
