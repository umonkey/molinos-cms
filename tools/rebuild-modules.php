<?php

require dirname(__FILE__) .'/../lib/bootstrap.php';

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
    $html = http::fetch('http://code.google.com/p/molinos-cms/downloads/list?q=label:Type-Module', http::CONTENT | http::NO_CACHE);

    if (!preg_match_all('@/files/([^"\']+\.zip)@', $html, $m))
      return array();

    return $m[1];
  }

  public function run()
  {
    $tmpdir = mcms::mkdir(os::path(mcms::config('tmpdir'), 'modules'));
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

$b = new Builder($argv[1]);
$b->run();
