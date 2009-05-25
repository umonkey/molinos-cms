<?php

try {
  require_once join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', 'lib', 'modules', 'core', 'class.loader.php'));
  Loader::setup();

  printf("Releasing version %s\n", MCMS_VERSION);

  if (!is_dir($tmp = os::path('tools', 'svn', '.svn'))) {
    printf("Please checkout dist/* as tools/svn, eg:\n\$ svn checkout http://molinos-cms.googlecode.com/svn/dist/%s tools/svn\n", MCMS_RELEASE);
    exit(1);
  }

  if (!is_dir($dirName = os::path('tools', 'svn', MCMS_RELEASE, 'changelogs')))
    if (!mkdir($dirName, 0750, true))
      throw new Exception('could not create ' . $dirName);
  printf("Updating ChangeLogs in %s/\n", $dirName);
  foreach (os::find('lib', 'modules', '*') as $tmp)
    if (os::exec(sprintf('git log -- %s > %s/%s.txt', $tmp, $dirName, $fileName = basename($tmp))))
      printf("  error writing {$fileName}\n");

  os::exec('git clean -fd');
} catch (Exception $e) {
  printf("ERROR: %s\n", $e->getMessage());
  exit(1);
}
