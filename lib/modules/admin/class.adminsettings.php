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

    $form->addControl(new ListControl(array(
      'value' => 'config_debuggers',
      'label' => t('IP адреса разработчиков'),
      'description' => t('Пользователям с этими адресами будут доступны отладочные функции (?debug=). Можно использовать маски, вроде 192.168.1.*'),
      'default' => mcms::config('debuggers'),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }
}
