<?php

class ExchangeMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();

    if (zip::isAvailable() and mcms::user()->hasAccess('d', 'type'))
      $icons[] = array(
        'group' => 'structure',
        'href' => '?q=admin&module=exchange',
        'title' => t('Бэкапы'),
        'description' => t('Бэкап и восстановление данных в формате XML.'),
        );

    return $icons;
  }
}
