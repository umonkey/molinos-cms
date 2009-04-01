<?php

class AdminExtSettings
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu()
  {
    return array(
      array(
        're' => 'admin/system/settings/adminext',
        'title' => t('Администрирование через сайт'),
        'method' => 'modman::settings',
        ),
      );
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.adminext
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'hide' => array(
        'type' => 'SetControl',
        'label' => t('Скрыть действия'),
        'options' => array(
          'delete' => 'Удаление объекта',
          'publish' => 'Публикация',
          'clone' => 'Клонирование',
          'locate' => 'Поиск объекта на сайте',
          'search' => 'Поиск документов пользователя',
          ),
        ),
      'edit_tpl' => array(
        'type' => 'TextLineControl',
        'label' => t('Шаблон ссылки на редактирование'),
        'default' => '?q=admin/edit/$id&destination=CURRENT',
        ),
      'groups' => array(
        'type' => 'SetControl',
        'label' => t('Команды доступны группам'),
        'dictionary' => 'group',
        ),
      ));
  }
}
