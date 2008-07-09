<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class BaseModule implements iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    $next = $ctx->get('destination', './');

    // mcms::debug($ctx);

    switch ($ctx->get('action')) {
    case 'login':
      mcms::log('base.rpc', $_SERVER['REQUEST_URI']);
      if (null !== ($otp = $ctx->get('otp'))) {
        try {
          $node = Node::load(array(
            'class' => 'user',
            'name' => $ctx->get('email'),
            ));

          if ($ctx->get('otp') == $node->otp) {
            $node->otp = null;
            $node->save();

            User::authorize($node->name, null, true);
            mcms::log('auth', $node->name .': logged in using otp');

            mcms::redirect($ctx->get('destination', './'));
          }
        } catch (ObjectNotFoundException $e) {
          throw new ForbiddenException(t('Пользователя с таким адресом нет.'));
        }

        mcms::debug();

        throw new ForbiddenException(t('Эта ссылка устарела.'));
      }

      try {
        if (null === $ctx->post('login'))
          User::authorize($ctx->get('id'), null);
        else
          User::authorize($ctx->post('login'), $ctx->post('password'));
      } catch (ObjectNotFoundException $e) {
        bebop_on_json(array(
          'status' => 'wrong',
          'message' => 'Неверный пароль или имя пользователя.',
          ));

        if (null !== ($tmp = $ctx->get('onerror')))
          $next = $tmp;
      }
      break;
    case 'logout':
      if (is_array($stack = mcms::session('uidstack'))) {
        $uid = array_pop($stack);
        mcms::session('uidstack', $stack);
        mcms::session()->save();
      } elseif (mcms::session('uid')) {
        mcms::session('uid', null);
        mcms::session()->save();
      }

      if (empty($uid))
        User::authorize();
      else
        self::login($uid);

      break;
    case 'su':
      if (!bebop_is_debugger() and mcms::config('debuggers'))
        throw new ForbiddenException(t('У вас нет прав доступа к sudo'));

      $curuid = User::identify()->id;

      $username = $ctx->get('username');

      if (empty($username))
        $uid = $ctx->get('uid');
      else {
        $node = Node::load(array('class' => 'user', 'name' => $username));
        $uid = $node->id;
      }

      if ($uid) {
        if (!is_array($stack = mcms::session('uidstack')))
          $stack = array();

        $stack[] = $curuid;
        mcms::session('uidstack', $stack);
        mcms::session()->save();

        self::login($sid, $uid);
      }
      else {
        mcms::redirect("admin");
      }
      break;

    case 'restore':
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

        $html = t("<p>Вы попросили напомнить ваш пароль для сайта %site. "
          ."Восстановить старый пароль мы не можем, и менять его не стали, "
          ."но вы можете войти, используя одноразовую ссылку. После входа "
          ."не забудьте установить новый пароль.</p>"
          ."<p><strong><a href='@url'>Войти</a></strong></p>"
          ."<p>Вы можете проигнорировать это сообщение, и ничего "
          ."не произойдёт.</p>", array(
            '%site' => $_SERVER['HTTP_HOST'],
            '@url' => $link = l("base.rpc?action=login&email={$email}"
              ."&otp={$node->otp}"
              ."&destination={$back}"),
            ));

        BebopMimeMail::send(null, $node->name, 'Восстановление пароля', $html);

        mcms::log('auth', $node->name .': password reset link: '. $link);

        $back->setarg('remind', 'mail_sent');

        bebop_on_json(array(
          'status' => 'sent',
          'message' => 'Новый пароль был отправлен на указанный адрес.',
          ));
      } catch (ObjectNotFoundException $e) {
        $back->setarg('remind', 'notfound');
        $back->setarg('remind_address', $ctx->post('identifier'));

        bebop_on_json(array(
          'status' => 'error',
          'message' => 'Пользователь с таким адресом на найден.',
          ));
      }

      $next = strval($back);
    }

    bebop_on_json(array(
      'status' => 'ok',
      ));

    mcms::redirect($next);
  }

  public static function login($uid)
  {
    $node = Node::load(array('class' => 'user', 'id' => $uid));

    if (!$node->published)
      throw new ForbiddenException(t('Ваш профиль заблокирован.'));

    mcms::session('uid', $node->id);
    mcms::session()->save();

    mcms::redirect("admin");
  }
};
