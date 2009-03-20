<?php

class BasicRestorePW
{
  /**
   * Возвращает форму восстановления пароля.
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
        'description' => t('На этот адрес будет выслано сообщение с инструкцией для восстановления пароля.'),
        ),
      ));

    return array('basicpw', t('Восстановить забытый пароль'), $schema);
  }

  /**
   * Восстановление пароля.
   * 
   * @param Context $ctx 
   * @param array $params 
   * @return void
   * @mcms_message ru.molinos.cms.auth.process.basicpw
   */
  public static function on_register(Context $ctx, array $params)
  {
    if (false === strpos($params['email'], '@'))
      throw new BadRequestException(t('Неверно введён почтовый адрес.'));

    $node = Node::load(array(
      'class' => 'user',
      'name' => $params['email'],
      ));

    if ($node->deleted)
      throw new ForbiddenException(t('Этот пользователь был удалён.'));
    elseif (!$node->published)
      throw new ForbiddenException(t('Этот пользователь заблокирован.'));

    $salt = md5($_SERVER['REMOTE_ADDR'] . microtime(true) . $node->name . rand());
    $node->otp = $salt;

    $ctx->db->beginTransaction();
    $node->save();
    $ctx->db->commit();

    $xml = html::em('request', array(
      'email' => $node->name,
      'host' => MCMS_HOST_NAME,
      'base' => $ctx->url()->getBase($ctx),
      'link' => '?q=authbasic.rpc&action=restore&email=' . urlencode($node->name)
        . '&otp=' . urlencode($salt),
      ));

    $xsl = $ctx->modconf('authbasic', 'restoretpl',
      os::path('lib', 'modules', 'authbasic', 'restore.xsl'));

    $html = xslt::transform($xml, $xsl, null);

    BebopMimeMail::send(null, $node->name, t('Восстановление пароля'), $html);
  }
}
