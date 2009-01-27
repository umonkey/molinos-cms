<?php

class AdminSettings implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new NodeLinkControl(array(
      'value' => 'config_admin_group',
      'label' => t('Административная группа'),
      'dictionary' => 'group',
      'description' => t('Пользователи из выбранной группы будут иметь доступ к настройке системы.'),
      'default_label' => t('(дать доступ всем)'),
      )));

    $form->addControl(new NodeLinkControl(array(
      'value' => 'config_user_group',
      'label' => t('Доступ к админке'),
      'dictionary' => 'group',
      'description' => t('Пользователи из выбранной группы смогут пользоваться административным интерфейсом.'),
      'default_label' => t('(разрешить всем пользователям)'),
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
