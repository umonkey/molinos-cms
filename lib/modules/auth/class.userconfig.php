<?php

class UserConfig
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu()
  {
    return array(
      array(
        're' => 'admin/system/settings/auth',
        'title' => t('Аутентификация'),
        'method' => 'modman::settings',
        'sort' => 'auth',
        ),
      );
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.auth
   */
  public static function on_get_settings(Context $ctx)
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
