<?php

require dirname(__FILE__) .'/../lib/modules/core/class.loader.php';
require dirname(__FILE__) .'/../lib/modules/base/class.mcms.php';
require dirname(__FILE__) .'/../lib/modules/base/class.http.php';
require dirname(__FILE__) .'/../lib/modules/base/class.zip.php';

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
      $html = http::fetch('http://code.google.com/p/molinos-cms/downloads/list?can=1&q=label:Type-Module+label:R9.03', http::CONTENT | http::NO_CACHE);
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

if ($argc < 2) {
  printf("Usage: %s path/to/modules.ini\n", basename(__FILE__));
  exit(1);
}

try {
  $ctx = new Context();
  $b = new Builder($argv[1]);
  $b->run();
} catch (Exception $e) {
  printf("ERROR: %s\nFILE: %s\nLINE: %d\n", $e->getMessage(), $e->getFile(), $e->getLine());
  exit(1);
}
