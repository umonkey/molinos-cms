<?php

class UserConfig
{
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
      'login_theme' => array(
        'type' => 'TextLineControl',
        'label' => t('Шкура для формы входа'),
        'description' => t('Используется шаблон login.xsl, если такого нет или в нём ошибка — используется встроенный.'),
        ),
      'allow_anonymous' => array(
        'type' => 'SetControl',
        'label' => t('Разрешить пользователям анонимно создавать'),
        'options' => Node::getSortedList('type', 'title', 'name'),
        'group' => t('Информация об авторстве'),
        'weight' => 50,
        ),
      'uid_weight' => array(
        'type' => 'NumberControl',
        'label' => t('Вес контрола'),
        'group' => t('Информация об авторстве'),
        'weight' => 51,
        ),
      'uid_label' => array(
        'type' => 'TextLineControl',
        'label' => t('Подпись контрола'),
        'default' => t('Ваше имя'),
        'group' => t('Информация об авторстве'),
        'weight' => 52,
        ),
      'uid_group' => array(
        'type' => 'TextLineControl',
        'label' => t('Имя группы'),
        'group' => t('Информация об авторстве'),
        'weight' => 53,
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
