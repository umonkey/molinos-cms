<?php

class AuthModule implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array(
      'title' => t('Настройка авторизации'),
      'class' => 'tabbed',
      ));

    $tab = new FieldSetControl(array(
      'name' => 'tab_openid',
      'label' => t('OpenID'),
      ));
    $tab->addControl(new EnumRadioControl(array(
      'value' => 'config_mode',
      'label' => t('Режим работы'),
      'options' => array(
        'open' => t('<strong>С прозрачной регистрацией</strong><br/>Максимальный комфорт для пользователя, регистрироваться в явном виде не нужно.'),
        'closed' => t('<strong>С ручной регистрацией</strong><br/>Использовать <a href=\'@url\'>OpenID</a> для авторизации, но добавлением пользователей в систему занимается администратор сайта.', array('@url' => 'http://ru.wikipedia.org/wiki/OpenID')),
        'none' => t('<strong>Отключить</strong><br/>Не понятно, кому и зачем этот режим может понадобиться, но пусть будет.'),
        ),
      'required' => true,
      'default' => 'open',
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'tab_anon',
      'label' => t('Анонимные пользователи'),
      ));
    $tab->addControl(new SetControl(array(
      'value' => 'config_groups_anon',
      'label' => t('Состоят в группах'),
      'options' => self::getGroups(),
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'tab_new',
      'label' => t('Новые'),
      ));
    $tab->addControl(new SetControl(array(
      'value' => 'config_groups',
      'label' => t('Добавляются в группы'),
      'options' => self::getGroups(),
      )));
    $form->addControl($tab);

    return $form;
  }

  private static function getGroups()
  {
    $result = array();

    foreach (Node::find(array('class' => 'group')) as $g)
      $result[$g->id] = $g->name;

    asort($result);

    return $result;
  }

  public static function hookPostInstall()
  {
  }
}
