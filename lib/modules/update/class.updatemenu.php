<?php

class UpdateMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();

    if ($message = self::getMessage())
      $icons[] = array(
        'group' => 'status',
        'message' => $message,
        );

    return $icons;
  }

  public static function getMessage()
  {
    if (file_exists($tmp = mcms::config('tmpdir') . DIRECTORY_SEPARATOR . 'update.txt')) {
      list($version, $filename) = explode(',', file_get_contents($tmp));

      return t('<span class="updates">Есть обновление CMS: %version, <a href="@url">установить</a>?</span>', array(
          '%version' => $version,
          '@url' => '?q=admin&cgroup=none&module=update'
          ));
    }
  }
}
