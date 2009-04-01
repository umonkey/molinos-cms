<?php

class ModManMenu
{
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
