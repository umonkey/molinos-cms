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
        $ini['filename'] = $zipname;
        zip::fromFolder($fullzipname = os::path($tmpdir, $ini['filename']), dirname($inifile));
        $ini['sha1'] = sha1_file($fullzipname);

        $this->modules[$module] = $ini;

        printf("new file: %s\n", $fullzipname);
      }
    }

    $header =
      "; Дата создания: " . mcms::now() . "\n"
      . ";\n"
      . "; Секции:\n"
      . ";   core = необходимая функциональность\n"
      . ";   base = базовая функциональность\n"
      . ";   admin = функции для администрирования\n"
      . ";   service = сервисные функции\n"
      . ";   blog = работа с блогами\n"
      . ";   spam = борьба со спамом\n"
      . ";   commerce = электронная коммерция\n"
      . ";   interaction = интерактив\n"
      . ";   performance = производительность\n"
      . ";   multimedia = мультимедийные функции\n"
      . ";   syndication = обмен данными между сайтами\n"
      . ";   templating = работа с шаблонами\n"
      . ";   visual = визуальные редакторы\n";

    ksort($this->modules);

    ini::write($this->inifile, $this->modules, $header);
  }
}

if ($argc < 2) {
  printf("Usage: %s path/to/modules.ini\n", basename(__FILE__));
  exit(1);
}

$b = new Builder($argv[1]);
$b->run();
