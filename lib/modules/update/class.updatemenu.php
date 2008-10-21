<?php

class UpdateMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();

    if (file_exists($tmp = mcms::config('tmpdir') .'/update.txt')) {
      list($version, $filename) = explode(',', file_get_contents($tmp));

      $icons[] = array(
        'group' => 'status',
        'message' => t('<span class="updates">Есть обновление CMS: %version, <a href="@url">установить</a>?</span>', array(
          '%version' => $version,
          '@url' => '?q=admin&cgroup=none&module=update'
          )),
        );
    }

    return $icons;
  }
}
