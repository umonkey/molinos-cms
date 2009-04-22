<?php

class AuthRPC extends RPCHandler
{
  public static function on_rpc(Context $ctx)
  {
    return parent::hookRemoteCall($ctx, __CLASS__);
  }

  /**
   * Обработка запросов на авторизацию. Переправляет в нужный модуль.
   * 
   * @param Context $ctx 
   * @return void
   */
  public static function rpc_post_auth(Context $ctx)
  {
    if (!($mode = $ctx->post('auth_type')))
      throw new BadRequestException(t('Не указан тип авторизации.'));

    $params = array();
    foreach ($ctx->post as $k => $v)
      if (0 === strpos($k, $mode . '_'))
        $params[substr($k, strlen($mode) + 1)] = $v;

    $result = $ctx->registry->unicast($message = 'ru.molinos.cms.auth.process.' . $mode, array($ctx, $params));

    if (false === $result)
      mcms::flog($message . ' not handled.');

    if (!($result instanceof Response))
      $result = $ctx->getRedirect();

    return $result;
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

  private static function login($uid)
  {
    $node = Node::load(array(
      'class' => 'user',
      'id' => $uid,
      ));

    if (!$node->published)
      throw new ForbiddenException(t('Ваш профиль заблокирован.'));

    mcms::session('uid', $node->id);
  }

  /**
   * Выход, завершение сеанса.
   * 
   * @param Context $ctx 
   * @return Response
   */
  public static function rpc_logout(Context $ctx)
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
}
