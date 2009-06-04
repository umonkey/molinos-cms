<?php

class BasicReg
{
  /**
   * Возвращает форму регистрации.
   * 
   * @return array
   * @mcms_message ru.molinos.cms.auth.enum
   */
  public static function on_get_form()
  {
    $schema = new Schema(array(
      'email' => array(
        'type' => 'EmailControl',
        'label' => t('Email'),
        'required' => true,
        ),
      'password1' => array(
        'type' => 'PasswordControl',
        'label' => t('Пароль'),
        'required' => true,
        ),
      'password2' => array(
        'type' => 'PasswordControl',
        'label' => t('Подтверждение пароля'),
        'required' => true,
        ),
      ));

    return array('basicreg', t('Зарегистрироваться на сайте'), $schema);
  }

  /**
   * Добавление нового пользователя.
   * 
   * @param Context $ctx 
   * @param array $params 
   * @return void
   * @mcms_message ru.molinos.cms.auth.process.basicreg
   */
  public static function on_register(Context $ctx, array $params)
  {
    if (empty($params['password1']))
      throw new BadRequestException(t('Пароль не может быть пустым.'));

    if ($params['password1'] != ($password = $params['password2']))
      throw new BadRequestException(t('Пароль подтверждён неверно.'));

    $ctx->db->beginTransaction();
    $node = Node::create(array(
      'class' => 'user',
      'name' => $params['email'],
      'published' => 1,
      ));
    $node->setPassword($password);
    $node->save();
    $ctx->db->commit();

    $ctx->user->login($node->name, $password);
  }
}
