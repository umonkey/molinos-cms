<?php

class UpdateScheduler implements iScheduler
{
  public static function taskRun()
  {
    if (($a = self::getAvailable()) == mcms::version())
      return;

    $url = 'http://molinos-cms.googlecode.com/files/'
      .'molinos-cms-'. $a .'.zip';

    try {
      if ($filename = mcms_fetch_file($url, false)) {
        $tmp = mcms::config('tmpdir') .'/update.txt';

        file_put_contents($tmp, $a .','. $tmp);
      }
    } catch (RuntimeException $e) {
      printf("error downloading an update: %s\n", $e->getMessage());
    }
  }

  /**
   * Поиск доступного обновления.
   *
   * @return string номер доступной версии.
   */
  private static function getAvailable()
  {
    $release = mcms::version(mcms::VERSION_RELEASE);
    $content = mcms_fetch_file('http://code.google.com/p/molinos-cms'
      .'/downloads/list?q=label:R'. $release);

    if (preg_match($re = "@http://molinos-cms\.googlecode\.com/files/molinos-cms-({$release}\.[0-9]+)\.zip@", $content, $m))
      return $m[1];
    else
      return $version;
  }
}
