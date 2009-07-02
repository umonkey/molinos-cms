<?php
/**
 * Создаёт отсутствующие страницы wiki.
 */

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'client.inc';

$paths = array();

foreach (os::find('lib/modules/*') as $moduleName) {
  $fileName = os::path('wiki', 'mod_' . basename($moduleName)) . '.wiki';
  $paths[] = $fileName;
}

foreach (os::find('lib/modules/*/route.ini') as $routeName) {
  $ini = ini::read($routeName);
  foreach (array_keys($ini) as $path) {
    if (2 == count($parts = explode('//', $path, 2))) {
      if (0 === strpos($parts[1], 'api/'))
        $paths[] = os::path('wiki', str_replace('.', '_', str_replace('/', '_', $parts[1])) . '.wiki');
    }
  }
}

foreach (os::find('lib/modules/*/module.ini') as $fileName) {
  $ini = ini::read($fileName);
  if (!empty($ini['messages']) and is_array($ini['messages']))
    foreach (array_keys($ini['messages']) as $messageName)
      $paths[] = $k = os::path('wiki', preg_replace('/[^a-z]+/', '_', $messageName) . '.wiki');
}

sort($paths);

foreach (array_unique($paths) as $path) {
  if (!file_exists($path)) {
    printf("+ %s\n", $path);
    touch($path);
  }
}
