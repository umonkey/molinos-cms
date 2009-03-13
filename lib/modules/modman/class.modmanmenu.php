<?php

class ModManMenu
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu.enum
   */
  public static function getMenuIcons(Context $ctx, array &$icons)
  {
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
  }
}
