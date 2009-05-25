<?php

try {
  require_once join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', 'lib', 'modules', 'core', 'class.loader.php'));
  Loader::setup();

  printf("Releasing version %s\n", MCMS_VERSION);

  if (!is_dir($tmp = os::path('tools', 'svn', '.svn'))) {
    printf("Please checkout dist/* as tools/svn, eg:\n\$ svn checkout http://molinos-cms.googlecode.com/svn/dist/%s tools/svn\n", MCMS_RELEASE);
    exit(1);
  }

  printf("Updating module metadata\n");
  Context::last()->registry->rebuildMeta();

  $status = array();
  os::exec('git st', null, $status);
  $break = false;
  foreach ($status as $line) {
    if (false !== strpos($line, 'modified: ') or false !== strpos($line, 'new: ')) {
      if (!$break)
        printf("Oustanding changes:\n");
      $break = true;
      printf("%s\n", $line);
    }
  }

  if ($break)
    throw new Exception('please fix these first.');

  if (!is_dir($dirName = os::path('tools', 'svn', MCMS_RELEASE, 'changelogs')))
    if (!mkdir($dirName, 0750, true))
      throw new Exception('could not create ' . $dirName);
  printf("Updating ChangeLogs in %s/\n", $dirName);
  foreach (os::find('lib', 'modules', '*') as $tmp) {
    $fileName = os::path($dirName, basename($tmp) . '.txt');
    if (os::exec(sprintf('git log -- %s > %s', $tmp, $fileName)))
      printf("  - %s\n", $fileName);
    else
      printf("  + %s\n", $fileName);
  }

  os::exec('git clean -fd');

  printf("Creating %s\n", $zipName = 'molinos-cms-' . MCMS_VERSION . '.zip');
  zip::create($zipName, array(
    '.htaccess.dist',
    '*.php',
    'doc',
    os::path('lib', 'modules', 'admin'),
    os::path('lib', 'modules', 'attachment'),
    os::path('lib', 'modules', 'auth'),
    os::path('lib', 'modules', 'base'),
    os::path('lib', 'modules', 'core'),
    os::path('lib', 'modules', 'cron'),
    os::path('lib', 'modules', 'install'),
    os::path('lib', 'modules', 'markdown'),
    os::path('lib', 'modules', 'modman'),
    os::path('lib', 'modules', 'nodeapi'),
    os::path('lib', 'modules', 'pdo'),
    os::path('lib', 'modules', 'routeadmin'),
    os::path('lib', 'modules', 'schema'),
    os::path('lib', 'modules', 'xslt'),
    os::path('lib', 'modules', 'widgetadmin'),
    os::path('tools', '*.php'),
    os::path('sites', 'default'),
    ), '@~$@');

  printf("Rebuilding modules.\n");
  $b = new Builder(os::path('tools', 'svn', MCMS_RELEASE, 'modules.ini'));
  $b->run();

  foreach (os::find('tmp', 'modules', '*.zip') as $zipName) {
    $name = basename($zipName);

    if (preg_match('@^(.*)-(.*)\.zip$@', $name, $m))
      $info = "{$m[1]} v{$m[2]}";
    else
      $info = $name;

    printf("Uploading %s\n", $zipName);

    if (os::exec('googlecode_upload.py', array('-s', $info, '-p', 'molinos-cms', '-l', 'Deprecated,Type-Module,R' . MCMS_RELEASE, $zipName)))
      printf("  error\n");
  }
} catch (Exception $e) {
  printf("ERROR: %s\n", $e->getMessage());
  exit(1);
}

class Builder
{
  private $inifile;
  private $modules;

  public function __construct($inifile)
  {
    $this->modules = ini::read($this->inifile = $inifile);
  }

  private function getExistingModules()
  {
    try {
      $html = http::fetch('http://code.google.com/p/molinos-cms/downloads/list?can=1&q=label:Type-Module+label:R' . MCMS_RELEASE, http::CONTENT | http::NO_CACHE);
    } catch (Exception $e) {
      return array();
    }

    if (!preg_match_all('@/files/([^"\']+\.zip)@', $html, $m))
      return array();

    return $m[1];
  }

  public function run()
  {
    $tmpdir = os::mkdir(os::path('tmp', 'modules'));
    $existing = $this->getExistingModules();

    foreach (glob(os::path('lib', 'modules', '*', 'module.ini')) as $inifile) {
      $module = basename(dirname($inifile));
      $ini = ini::read($inifile);

      foreach (array('section', 'priority', 'version', 'name') as $k) {
        if (!array_key_exists($k, $ini)) {
          printf("warning: %s has no '%s' key, module ignored.\n", $module, $k);
          continue 2;
        }
      }

      if (!in_array($zipname = $module . '-' . $ini['version'] . '.zip', $existing)) {
        zip::fromFolder($fullzipname = os::path($tmpdir, $zipname), dirname($inifile));
        printf("new file: %s\n", basename($fullzipname));
      }

      foreach ($ini as $k => $v)
        if (is_array($v))
          unset($ini[$k]);

      $ini['filename'] = $zipname;
      $this->modules[$module] = $ini;
    }

    ksort($this->modules);

    ini::write($this->inifile, $this->modules);
  }
}
