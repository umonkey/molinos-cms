<?php

class AdminSettings
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module.admin
   */
  public static function formGetModuleConfig()
  {
    return new Schema(array(
      'admin' => array(
        'type' => 'NodeLinkControl',
        'label' => t('Администратор сервера'),
        'dictionary' => 'user',
        'required' => true,
        'description' => t('Выберите пользователя, который занимается администрированием этого сайта. На его почтовый адрес будут приходить сообщения о состоянии системы.'),
        ),
      'debuggers' => array(
        'type' => 'ListControl',
        'label' => t('IP адреса разработчиков'),
        'description' => t('Пользователям с этими адресами будут доступны отладочные функции (?debug=). Можно использовать маски, вроде 192.168.1.*'),
        'default' => array(
          '127.0.0.1',
          $_SERVER['REMOTE_ADDR'],
          ),
        ),
      ));
  }
}
