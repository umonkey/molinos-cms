<?php

class AuthRPC
{
  /**
   * Обработка запросов на авторизацию. Переправляет в нужный модуль.
   * 
   * @param Context $ctx 
   * @return Redirect
   */
  public static function rpc_post_login(Context $ctx)
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
  public static function rpc_get_su(Context $ctx)
  {
    $user = Node::load(array(
      'class' => 'user',
      'deleted' => 0,
      'id' => $ctx->get('id'),
      ), $ctx->db)->knock('c');

    $curuid = $ctx->user->id;

    if ($user->id and $user->id != $curuid) {
      if (!is_array($stack = mcms::session('uidstack')))
        $stack = array();

      $stack[] = $curuid;
      mcms::session('uidstack', $stack);

      self::login($user->id);
    }

    return $ctx->getRedirect();
  }

  /**
   * Переключается в указанного пользователя, проверяет его статус.
   */
  private static function login($uid)
  {
    if ($uid) {
      $data = Context::last()->db->fetch("SELECT `id`, `published` FROM `node` WHERE `class` = 'user' AND `deleted` = 0 AND `id` = ?", array($uid));
      if (empty($data))
        throw new ForbiddenException(t('Нет такого пользователя.'));
      elseif (empty($data['published']))
        throw new ForbiddenException(t('Ваш профиль заблокирован.'));
    }

    User::storeSessionData($uid);
  }

  /**
   * Выход, завершение сеанса.
   * 
   * @param Context $ctx 
   * @return Response
   */
  public static function rpc_get_logout(Context $ctx)
  {
    $uid = null;

    $stack = (array)mcms::session('uidstack');
    $uid = array_pop($stack);
    mcms::session('uidstack', $stack);

    if (empty($uid))
      $ctx->user->logout();
    else
      self::login($uid);

    $next = $uid
      ? $ctx->get('from', '')
      : '';

    return $ctx->getRedirect($next);
  }
}
