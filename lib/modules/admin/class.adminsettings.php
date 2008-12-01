<?php

class AdminSettings implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new NodeLinkControl(array(
      'value' => 'config_admin',
      'label' => t('Администратор сервера'),
      'dictionary' => 'user',
      'required' => true,
      'description' => t('Выберите пользователя, который занимается администрированием этого сайта. На его почтовый адрес будут приходить сообщения о состоянии системы.'),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }
}
