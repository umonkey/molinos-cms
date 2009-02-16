<?php

class UserConfig implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new SetControl(array(
      'value' => 'config_new_user_groups',
      'label' => t('Группы для регистрирующихся пользователей'),
      'options' => Node::getSortedList('group'),
      'store' => true,
      )));
    $form->addControl(new SetControl(array(
      'value' => 'config_special_profile_fields',
      'label' => t('Запретить пользователям редактировать поля'),
      'options' => self::getProfileFields(),
      'store' => true,
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_check_pw_on_profile_edit',
      'label' => t('Проверять пароль при изменении профиля'),
      )));

    return $form;
  }

  private static function getProfileFields()
  {
    $result = array();

    foreach (Schema::load('user') as $k => $v)
      $result[$k] = $v->label;

    if (isset($result['groups']))
      unset($result['groups']);

    asort($result);
    return $result;
  }
}
