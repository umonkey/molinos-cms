<?php

class ModManMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();

    if (count(modman::getUpdatedModules()))
      $icons[] = array(
        'group' => 'status',
        'message' => t('Есть обновления для некоторых модулей.'),
        'link' => '?q=admin.rpc&action=form&module=modman&mode=upgrade&cgroup=system&destination=CURRENT',
        );

    $icons[] = array(
      'group' => 'system',
      'href' => '?action=form&module=modman&mode=settings',
      'title' => t('Настройки'),
      );

    $icons[] = array(
      'group' => 'system',
      'href' => '?action=form&module=modman&mode=addremove',
      'title' => t('Модули'),
      );

    return $icons;
  }
}
