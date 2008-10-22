<?php

class AdminExtSettings implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new SetControl(array(
      'value' => 'config_groups',
      'label' => t('Разрешённые группы'),
      'options' => Node::getSortedList('group'),
      'description' => t('Доступ к выпадающему меню будет только '
        .'у указанных групп.'),
      )));

    $form->addControl(new SetControl(array(
      'value' => 'config_hide',
      'label' => t('Скрыть действия'),
      'options' => array(
        'delete' => 'Удаление объекта',
        'publish' => 'Публикация',
        'clone' => 'Клонирование',
        'locate' => 'Поиск объекта на сайте',
        'search' => 'Поиск документов пользователя',
        ),
      )));

    $form->addControl(new TextLineControl(array(
      'value' => 'config_edit_tpl',
      'label' => t('Шаблон ссылки на редактирование'),
      'default' => 'admin/content/edit/$id?destination=CURRENT',
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }
}
