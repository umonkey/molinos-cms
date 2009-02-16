<?php

class SchemaMenu implements iAdminMenu
{
  public static function getMenuIcons(Context $ctx)
  {
    $icons = array();
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

    return $icons;
  }
}
