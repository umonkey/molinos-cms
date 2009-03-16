<?php

class ModManMenu
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu.enum
   */
  public static function getMenuIcons(Context $ctx, array &$icons)
  {
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

  /**
   * @mcms_message ru.molinos.cms.admin.status.enum
   */
  public static function on_enum_notifications(Context $ctx, array &$messages)
  {
    $updated = modman::getUpdatedModules();

    if (count($updated))
      $messages[] = array(
        'message' => t('Есть обновления для некоторых модулей.'),
        'link' => '?q=admin.rpc&action=form&module=modman&mode=upgrade&cgroup=system&destination=CURRENT',
        );
  }
}
