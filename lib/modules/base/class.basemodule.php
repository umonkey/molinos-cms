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
      try {
        User::authorize($_POST['login'], $_POST['password']);
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
      $sid = $_COOKIE['mcmsid'];
      session_name('mcmsid');
      session_id($sid);
      session_start();
      session_save_path(mcms::mkdir(mcms::config('tmpdir') .'/sessions'));

      if (is_array($_SESSION['uidstack']))
        $uid = array_pop($_SESSION['uidstack']);

      session_commit();

      if (empty($uid))
        User::authorize();
      else
        self::login($_COOKIE['mcmsid'], $uid);

      break;
    case 'su':
      if (!bebop_is_debugger() and mcms::config('debuggers'))
        throw new ForbiddenException(t('У вас нет прав доступа к sudo'));

      $curuid = User::identify()->id;

      $sid = $_COOKIE['mcmsid'];
      $username = $ctx->get('username');
      if (empty($username))
        $uid = $ctx->get('uid');
      else {
        $node = Node::load(array('class' => 'user', 'name' => $username));
        $uid = $node->id;
      }

      session_name('mcmsid');
      session_id($sid);
      session_start();
      session_save_path(mcms::mkdir(mcms::config('tmpdir') .'/sessions'));
      if ($uid) {
        if (!is_array($_SESSION['uidstack']))
          $_SESSION['uidstack'] = array();

        $_SESSION['uidstack'][] = $curuid;
        session_commit();
        self::login($sid, $uid);
      }
      else {
        mcms::redirect("admin");
      }
      break;
    }
    bebop_on_json(array('status' => 'ok'));

    mcms::redirect($next);
  }

  public static function login($sid, $uid)
  {
    $node = Node::load(array('class' => 'user', 'id' => $uid));

    if (!$node->published)
      throw new ForbiddenException(t('Ваш профиль заблокирован.'));

    // Сохраняем сессию в БД.
    SessionData::db($sid, array('uid' => $node->id));
    User::setcookie($sid);
    mcms::redirect("admin");
  }
};
