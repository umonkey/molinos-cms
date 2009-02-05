<?php

class ExchangeMenu implements iAdminMenu
{
  /**
   * Отключено в связи с неработоспособностью, см.
   * http://code.google.com/p/molinos-cms/issues/detail?id=613
   */
  public static function getMenuIcons()
  {
    return;

    $icons = array();

    if (zip::isAvailable() and Context::last()->user->hasAccess('d', 'type'))
      $icons[] = array(
        'group' => 'system',
        'href' => '?q=admin&module=exchange&cgroup=system',
        'title' => t('Бэкапы'),
        'description' => t('Бэкап и восстановление данных в формате XML.'),
        );

    return $icons;
  }
}
