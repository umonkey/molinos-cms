<?php

class SchemaMenu
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu(Context $ctx)
  {
    return array(
      array(
        're' => 'admin/structure/types',
        'method' => 'on_get_types',
        'title' => t('Типы документов'),
        'description' => t('Настройка прав доступа к документам разных типов, изменение списка разделов, в которых документы могут находиться.'),
        'sort' => 'schema01',
        ),
      array(
        're' => 'admin/structure/fields',
        'method' => 'on_get_fields',
        'title' => t('Поля документов'),
        'description' => t('Управление полями, используемыми в документах, возможностью сортировать по ним и делать выборки.'),
        'sort' => 'schema02',
        ),
      );
  }

  public static function on_get_types(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('schema');
  }

  public static function on_get_fields(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('fields');
  }
}
