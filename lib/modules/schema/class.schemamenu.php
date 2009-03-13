<?php

class SchemaMenu
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu.enum
   */
  public static function getMenuIcons(Context $ctx, array &$icons)
  {
    $user = $ctx->user;

    if ($user->hasAccess('u', 'type'))
      $icons[] = array(
        'group' => 'structure',
        'href' => 'action=list&preset=schema',
        'title' => t('Типы документов'),
        );

    $icons[] = array(
      'group' => 'structure',
      'title' => t('Поля'),
      'href' => 'action=list&type=field',
      );
  }
}
