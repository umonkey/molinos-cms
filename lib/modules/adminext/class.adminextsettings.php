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

    return $form;
  }

  public static function hookPostInstall()
  {
  }
}
