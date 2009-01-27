<?php

class ModManMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();

    if (mcms::isAdmin()) {
      if ($message = self::getMessage())
        $icons[] = array(
          'group' => 'status',
          'message' => $message,
          );

      $icons[] = array(
        'group' => 'system',
        'href' => '?q=admin&cgroup=system&module=modman&mode=settings',
        'title' => t('Настройки'),
        );

      $icons[] = array(
        'group' => 'system',
        'href' => '?q=admin&cgroup=system&module=modman&mode=addremove',
        'title' => t('Модули'),
        );
    }

    return $icons;
  }

  public static function getMessage()
  {
    if (count(modman::getUpdatedModules()))
      return t('<p class="important">Для некоторых модулей CMS есть обновления, <a href="@url">установите</a> их.</p>', array(
          '@url' => '?q=admin&cgroup=system&module=modman&mode=upgrade&destination=CURRENT'
          ));
  }
}
