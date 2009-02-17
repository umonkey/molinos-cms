<?php

class UserRPC extends RPCHandler implements iRemoteCall
{
  protected static function rpc_post_login(Context $ctx)
  {
    try {
      if (null !== ($otp = $ctx->get('otp'))) {
        $ctx->checkMethod('get');

        try {
          $node = Node::load(array(
            'class' => 'user',
            'name' => $ctx->get('email'),
            ));

          if ($ctx->get('otp') == $node->otp) {
            $node->otp = null;
            $node->save();

            User::authorize($node->name, null, true);
            mcms::flog($node->name .': logged in using otp');

            return $ctx->getRedirect();
          }
        } catch (ObjectNotFoundException $e) {
          throw new ForbiddenException(t('Пользователя с таким адресом нет.'));
        }

        throw new ForbiddenException(t('Ссылкой для восстановления пароля '
          .'можно воспользоваться всего один раз, и этой ссылкой кто-то '
          .'уже воспользовался.'));
      }

      $ctx->checkMethod('post');

      if (null === $ctx->post('login'))
        User::authorize($ctx->get('id'), null, $ctx);
      else
        User::authorize($ctx->post('login'), $ctx->post('password'), $ctx);
    }

    catch (ForbiddenException $e) {
      if ($next = $ctx->get('onerror'))
        return new Redirect($next);

      throw $e;
    }

    return true;
  }

  protected static function rpc_logout(Context $ctx)
  {
    $uid = null;

    if (is_array($stack = mcms::session('uidstack'))) {
      $uid = array_pop($stack);
      mcms::session('uidstack', $stack);
    } elseif (mcms::session('uid')) {
      mcms::session('uid', null);
    }

    if (empty($uid))
      User::authorize(null, null, $ctx);
    else
      self::login($uid);

    $next = $uid
      ? $ctx->get('from', '')
      : '';

    return $ctx->getRedirect($next);
  }

  /**
   * Переключение в контекст произвольного пользователя.
   *
   * Используется для отладки проблем, специфичных для пользователей.
   * Пользователь, в чей контекст нужно переключиться, указывается
   * в GET-параметрах "uid" или "username", числовой идентификатор
   * или логин, соответственно.
   *
   * При выходе из системы будет возвращена ранее активная сессия.
   *
   * Это действие доступно только отладчикам.
   *
   * @param Context $ctx используется для доступа к GET/POST данным.
   * @return string адрес перенаправления пользователя.
   */
  protected static function rpc_get_su(Context $ctx)
  {
    if (!$ctx->canDebug())
      throw new ForbiddenException(t('У вас нет прав доступа к sudo'));

    if (null === ($uid = $ctx->get('uid'))) {
      if ($username = $ctx->get('username'))
        $uid = Node::load(array('class' => 'user', 'name' => $username))->id;
      else
        throw new PageNotFoundException(t('Нет такого пользователя.'));
    }

    $curuid = $ctx->user->id;

    if ($uid and $uid != $curuid) {
      if (!is_array($stack = mcms::session('uidstack')))
        $stack = array();

      $stack[] = $curuid;
      mcms::session('uidstack', $stack);

      self::login($uid);
    }
  }

  /**
   * Восстановление пароля.
   *
   * Email пользователя передаётся через POST-параметр "identifier",
   * на этот email отправляется ссылка, позволяющая однократно войти
   * в систему без пароля.
   *
   * Изменение пароля происходит в момент перехода по полученной
   * ссылке.  Ссылка привязывается в том числе и к текущему паролю
   * пользователя, поэтому если вспомнить пароль и изменить его,
   * неиспользованные одноразовые ссылки перестанут работать.
   *
   * @param Context $ctx используется для доступа к GET/POST данным.
   * @return string адрес перенаправления пользователя.
   */
  public static function rpc_restore(Context $ctx)
  {
    $back = new url($ctx->post('destination'));

    try {
      $node = Node::load(array(
        'class' => 'user',
        'name' => $email = $ctx->post('identifier'),
        ));

      $salt = $_SERVER['REMOTE_ADDR'] . microtime(true) .
        $email . rand();

      $node->otp = md5($salt);
      $node->save();

      $back->setarg('remind', null);
      $back->setarg('remind_address', null);
      $backstr = $back->string();

      $html = t("<p>Вы попросили напомнить ваш пароль для сайта %site. "
        ."Восстановить старый пароль мы не можем, и менять его не стали, "
        ."но вы можете войти, используя одноразовую ссылку. После входа "
        ."не забудьте установить новый пароль.</p>"
        ."<p><strong><a href='@url'>Войти</a></strong></p>"
        ."<p>Вы можете проигнорировать это сообщение, и ничего "
        ."не произойдёт.</p>", array(
          '%site' => $_SERVER['HTTP_HOST'],
          '@url' => $link = l("?q=user.rpc&action=login&email={$email}"
            ."&otp={$node->otp}"
            ."&destination={$backstr}"),
          ));

      BebopMimeMail::send(null, $node->name, 'Восстановление пароля', $html);

      $back->setarg('remind', 'mail_sent');
    } catch (ObjectNotFoundException $e) {
      $back->setarg('remind', 'notfound');
      $back->setarg('remind_address', $ctx->post('identifier'));
    }

    return new Redirect($back->string());
  }

  /**
   * Подтверждение регистрации.
   */
  public static function rpc_register_confirm(Context $ctx)
  {
    $ctx->checkMethod('get');

    if (!is_array($data = unserialize(mcms_decrypt($ctx->get('hash')))))
      throw new PageNotFoundException(t('Не удалось расшифровать вашу просьбу, кто-то повредил ссылку.'));

    $node = Node::create('user', array(
      'published' => true,
      ))->formProcess($data)->save();

    User::authorize($node->name, null, true);

    $ctx->redirect('', Redirect::OTHER, $node);
  }

  public static function rpc_register(Context $ctx)
  {
    $ctx->checkMethod('post');

    // Валидируем данные.
    $node = Node::create('user')->formProcess($ctx->post);

    // Получаем почтовый адрес.
    $email = $node->getEmail();

    // Кодируем данные для передачи в email.
    $hash = mcms_encrypt(serialize($ctx->post));

    // Формируем послание.
    $message = t('<p>Здравствуйте.&nbsp; От вашего имени пришёл запрос на регистрацию '
      . 'на сайте %site. Если этот запрос действительно исходит от вас, '
      . 'пройдите по <a href=\'@url\'>этой ссылке</a>, чтобы завершить '
      . 'процесс регистраци.&nbsp; Чтобы отменить регистрацию, удалите '
      . 'или проигнорируйте это сообщение.</p>', array(
        '%site' => url::host(),
        '@url' => '?q=user.rpc&action=register_confirm&hash='. urlencode($hash),
        ));

    $subject = t('Регистрация на %site', array(
      '%site' => url::host(),
      ));

    if (!BebopMimeMail::send(null, $email, $subject, $message))
      throw new RuntimeException(t('Не удалось отправить письмо на адрес %email', array(
        '%email' => $email,
        )));
  }

  private static function login($uid)
  {
    $node = Node::load(array('class' => 'user', 'id' => $uid));

    if (!$node->published)
      throw new ForbiddenException(t('Ваш профиль заблокирован.'));

    mcms::session('uid', $node->id);
  }

  /**
   * Используется (???) для работы OpenID.
   * Кажется это давно было перенесено в mod_openid.
   *
   * @param Context $ctx используется для доступа к GET/POST данным.
   * @return void
   */
  public static function rpc_openid(Context $ctx)
  {
    if (!empty($_GET['openid_mode']))
      $node = OpenIdProvider::openIDAuthorize($_GET['openid_mode'], $ctx);
    mcms::debug($node, $_GET);
  }
}