<?php

class ModeratorInstaller implements iInstaller
{
  public static function onInstall(Context $ctx)
  {
    // Добавляем группы «Выпускающие редакторы»
    if (!Node::count(array('class' => 'group', 'login' => 'Publishers'))) {
      $tmp = Node::create('group', array(
        'name' => t('Выпускающие редакторы'),
        'login' => 'Publishers',
        'description' => t('Пользователи из этой группы могут публиковать документы и изменять опубликованные, изменения других пользователей будут проходить через модератора.'),
        ));
      $tmp->save();
    }

    // Добавляем свойство пользователя.
    $spec = array(
      'label' => t('Выпускающий редактор'),
      'type' => 'NodeLinkControl',
      'dictionary' => 'user',
      'required' => false,
      'description' => t('Все изменения, производимые этим пользователем и потенциально отражаемые на сайте, будут требовать одобрения указанного здесь модератора.  Вводить следует логин (внутреннее имя) пользователя с ролью «Выпускающий редактор».'),
      );

    $type = Node::load(array('class' => 'type', 'name' => 'user'));
    if ($type->fieldSet('publisher', $spec))
      $type->save();
  }

  public static function onUninstall(Context $ctx)
  {
  }
}
