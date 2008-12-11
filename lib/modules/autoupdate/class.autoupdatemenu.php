<?php

class AutoUpdateMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();

    // mcms::debug(AutoUpdater::getUpdatedModules());

    $icons[] = array(
      'group' => 'system',
      'href' => '?q=admin&cgroup=system&module=autoupdate',
      'title' => t('Обновления'),
      );

    if ($count = count(AutoUpdater::getUpdatedModules()))
      $icons[] = array(
        'group' => 'status',
        'message' => t('Есть <a href="@url">обновления</a> для используемых модулей.', array(
          '@url' => '?q=admin&cgroup=system&module=autoupdate&mode=update&destination=CURRENT',
          )),
        );

    return $icons;
  }
}
