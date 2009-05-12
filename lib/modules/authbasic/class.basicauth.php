<?php

class BasicAuth
{
  /**
   * Возвращает форму парольной авторизации.
   * 
   * @return array
   * @mcms_message ru.molinos.cms.auth.enum
   */
  public static function on_auth_get_form()
  {
    $schema = new Schema(array(
      'login' => array(
        'type' => 'EmailControl',
        'label' => t('Email'),
        'required' => true,
        ),
      'password' => array(
        'type' => 'PasswordControl',
        'label' => t('Пароль'),
        'required' => true,
        ),
      'remember' => array(
        'type' => 'BoolControl',
        'label' => t('Помнить меня 2 недели'),
        ),
      ));

    return array('basic', t('Ввести логин и пароль'), $schema);
  }

  /**
   * Проверка пароля и идентификация пользователя.
   * 
   * @param Context $ctx 
   * @param array $params 
   * @return void
   * @mcms_message ru.molinos.cms.auth.process.basic
   */
  public static function on_auth(Context $ctx, array $params)
  {
    if (empty($params['login']))
      throw new BadRequestException(t('Не указан email.'));

    if (false === strpos($login = $params['login'], '@'))
        throw new BadRequestException(t('Введённый идентификатор не похож на email.'));

    $ctx->user->login($login, $params['password']);
  }
}
