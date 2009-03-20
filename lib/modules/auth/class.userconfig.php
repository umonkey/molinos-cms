<?php

class UserConfig
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module.auth
   */
  public static function formGetModuleConfig()
  {
    return new Schema(array(
      'new_user_groups' => array(
        'type' => 'SetControl',
        'label' => t('Группы для регистрирующихся пользователей'),
        'options' => Node::getSortedList('group'),
        'store' => true,
        ),
      'special_profile_fields' => array(
        'type' => 'SetControl',
        'label' => t('Запретить пользователям редактировать поля'),
        'options' => self::getProfileFields(),
        'store' => true,
        ),
      'check_pw_on_profile_edit' => array(
        'type' => 'BoolControl',
        'label' => t('Проверять пароль при изменении профиля'),
        ),
      ));
  }

  private static function getProfileFields()
  {
    $result = array();

    foreach (Schema::load(Context::last()->db, 'user') as $k => $v)
      $result[$k] = $v->label;

    if (isset($result['groups']))
      unset($result['groups']);

    asort($result);
    return $result;
  }
}
