<?php
/**
 * Получение информации о доступных модулях.
 *
 * @package mod_autoupdate
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

class AutoUpdateScheduler implements iScheduler
{
  public static function taskRun(Context $ctx)
  {
    $modules = array();

    foreach (self::getUrls() as $url) {
      if (($file = mcms_fetch_file($url, false, false))) {
        $ini = ini::read($file);

        foreach ($ini as $k => $v) {
          if (!array_key_exists($k, $modules))
            $modules[$k] = $v;
          elseif (version_compare($v['version'], $modules[$k]['version'], '>'))
            $modules[$k] = $v;
        }
      }
    }

    foreach ($modules as $k => $v) {
      $local = os::path('lib', 'modules', $k, 'module.ini');

      if (file_exists($local)) {
        $ini = ini::read($local);
        $v['version.local'] = $ini['version'];
      }

      ksort($v);
      $modules[$k] = $v;
    }

    ini::write(AutoUpdater::getInfoPath(), $modules);
  }

  private static function getUrls()
  {
    $urls = array();

    $urls[] = 'http://molinos-cms.googlecode.com/svn/dist/' . mcms::version(mcms::VERSION_RELEASE) . '/modules.ini';

    return $urls;
  }
}
