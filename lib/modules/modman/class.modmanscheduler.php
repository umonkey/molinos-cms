<?php

class ModManScheduler implements iScheduler
{
  public static function taskRun(Context $ctx)
  {
    modman::updateDB();
  }

  /**
   * Поиск доступного обновления.
   *
   * @return string номер доступной версии.
   */
  private static function getAvailable()
  {
    $release = mcms::version(mcms::VERSION_RELEASE);
    $content = http::fetch('http://code.google.com/p/molinos-cms'
      .'/downloads/list?q=label:R'. $release, http::CONTENT);

    if (preg_match($re = "@http://molinos-cms\.googlecode\.com/files/molinos-cms-({$release}\.[0-9]+)\.zip@", $content, $m))
      return $m[1];
    else
      return $version;
  }
}
