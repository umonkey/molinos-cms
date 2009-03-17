<?php

class OpenIdSettings
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module.openid
   */
  public static function formGetModuleConfig()
  {
    return new Schema(array(
      'mode' => array(
        'type' => 'EnumRadioControl',
        'label' => t('Режим работы'),
        'options' => array(
          'open' => t('Прозрачная регистрация'),
          'closed' => t('Только авторизация, без регистрации'),
          ),
        'required' => true,
        'default' => 'open',
        ),
      'profile_fields' => array(
        'type' => 'SetControl',
        'label' => t('Запрашиваемые атрибуты'),
        'options' => array(
          'email' => 'Email',
          'fullname' => 'Полное имя',
          'dob' => 'Дата рождения',
          'gender' => 'Пол',
          'postcode' => 'Почтовый индекс',
          'country' => 'Страна',
          'language' => 'Предпочтительный язык',
          'timezone' => 'Часовой пояс',
          ),
        'description' => t('Поля приведены в соответствии со <a href=\'@url\'>спецификацией</a>.', array('@url' => 'http://openid.net/specs/openid-simple-registration-extension-1_0.html#response_format')),
        ),
      ));
  }

  private static function getGroups()
  {
    $result = array();

    foreach (Node::find(Context::last()->db, array('class' => 'group')) as $g)
      $result[$g->id] = $g->name;

    asort($result);

    return $result;
  }
}
