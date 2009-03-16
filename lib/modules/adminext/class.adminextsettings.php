<?php

class AdminExtSettings
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module
   */
  public static function formGetModuleConfig()
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
        'default' => '?q=admin/content/edit/$id&destination=CURRENT',
        ),
      'groups' => array(
        'type' => 'SetControl',
        'label' => t('Команды доступны группам'),
        'dictionary' => 'group',
        ),
      ));
  }
}
