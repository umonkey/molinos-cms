<?php
/**
 * Обработчик RPC модуля base.
 *
 * Выполняет действие, указанное в параметре action.  Возможные варианты:
 * вход в систему (login), выход (logout), имперсонация (su), восстановление
 * забытого пароля (restore), промежуточную обработку OpenID (openid).
 *
 * @package mod_base
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Обработчик RPC модуля base.
 *
 * Обработчики отдельных команд (методы onRemote...()) сделаны защищёнными
 * для того, чтобы их можно было использовать в тестах (унаследовав тестовый
 * класс от BaseModule).
 *
 * @package mod_base
 */
class BaseModule implements iRemoteCall, iModuleConfig, iNodeHook
{
  /**
   * Маршрутизатор вызовов.
   *
   * В зависимости от значения GET-параметра action вызывает один из
   * методов onRemote.  Результат работы метода — адрес для редиректа.
   *
   * При успешно обработанных запросах от XMLHttpRequest возвращает
   * JSON объект с параметром status=ok.
   *
   * @param RequestContext $ctx используется для доступа к GET/POST данным.
   * @return void
   */
  public static function hookRemoteCall(RequestContext $ctx)
  {
    $next = null;
    $methods = array('login', 'logout', 'su', 'restore', 'openid');

    if (in_array($ctx->get('action'), $methods)) {
      $method = 'onRemote'. ucfirst($ctx->get('action'));
      $next = call_user_func(array(__CLASS__, $method), $ctx);
    }

    bebop_on_json(array(
      'status' => 'ok',
      ));

    if (null === $next)
      $next = $ctx->get('destination', '/');

    mcms::redirect($next);
  }

  private static function login($uid)
  {
    $node = Node::load(array('class' => 'user', 'id' => $uid));

    if (!$node->published)
      throw new ForbiddenException(t('Ваш профиль заблокирован.'));

    mcms::session('uid', $node->id);
  }

  /**
   * Вход в систему.
   *
   * Логин и пароль передаются через POST-параметры "login" и "password".
   * Если указан GET-параметр onerror, он используется как адрес для
   * редиректа при ошибке входа.
   *
   * @param RequestContext $ctx используется для доступа к GET/POST данным.
   * @return string адрес перенаправления пользователя.
   */
  protected static function onRemoteLogin(RequestContext $ctx)
  {
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

      throw new ForbiddenException(t('Ссылкой для восстановления пароля '
        .'можно воспользоваться всего один раз, и этой ссылкой кто-то '
        .'уже воспользовался.'));
    }

    if ('POST' != $_SERVER['REQUEST_METHOD'])
      throw new ForbiddenException('Идентификация возможна только '
        .'методом POST.');

    try {
      if (null === $ctx->post('login'))
        User::authorize($ctx->get('id'), null);
      else
        User::authorize($ctx->post('login'), $ctx->post('password'));

      mcms::log('base.rpc', 'logged in');
    } catch (ObjectNotFoundException $e) {
      bebop_on_json(array(
        'status' => 'wrong',
        'message' => 'Неверный пароль или имя пользователя.',
        ));

      if (null !== ($tmp = $ctx->get('onerror')))
        $next = $tmp;
    }

    return $next;
  }

  /**
   * Закрытие сессии, переход в анонимный режим.
   *
   * Если пользователь был ранее идентифицирован с помощью
   * action=su, происходит возврат к предыдущему пользователю.
   *
   * @param RequestContext $ctx используется для доступа к GET/POST данным.
   * @return string адрес перенаправления пользователя.
   */
  protected static function onRemoteLogout(RequestContext $ctx)
  {
    if (is_array($stack = mcms::session('uidstack'))) {
      $uid = array_pop($stack);
      mcms::session('uidstack', $stack);
    } elseif (mcms::session('uid')) {
      mcms::session('uid', null);
    }

    if (empty($uid))
      User::authorize();
    else
      self::login($uid);
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
   * @param RequestContext $ctx используется для доступа к GET/POST данным.
   * @return string адрес перенаправления пользователя.
   */
  protected static function onRemoteSu(RequestContext $ctx)
  {
    if (!bebop_is_debugger())
      throw new ForbiddenException(t('У вас нет прав доступа к sudo'));

    if (null === ($uid = $ctx->get('uid'))) {
      if ($username = $ctx->get('username'))
        $uid = Node::load(array('class' => 'user', 'name' => $username))->id;
      else
        throw new PageNotFoundException(t('Нет такого пользователя.'));
    }

    $curuid = mcms::user()->id;

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
   * @param RequestContext $ctx используется для доступа к GET/POST данным.
   * @return string адрес перенаправления пользователя.
   */
  protected static function onRemoteRestore(RequestContext $ctx)
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

    return strval($back);
  }

  /**
   * Используется (???) для работы OpenID.
   * Кажется это давно было перенесено в mod_openid.
   *
   * @param RequestContext $ctx используется для доступа к GET/POST данным.
   * @return void
   */
  protected static function onRemoteOpenid(RequestContext $ctx)
  {
    if (!empty($_GET['openid_mode']))
      $node = OpenIdProvider::openIDAuthorize($_GET['openid_mode']);
    mcms::debug($node, $_GET);
  }

  /**
   * Возвращает форму для настройки модуля.
   *
   * TODO: вынести в BaseModuleSettings.
   *
   * @return Form форма для настройки модуля.
   */
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new NumberControl(array(
      'value' => 'config_archive_limit',
      'label' => t('Количество архивных ревизий'),
      'default' => 10,
      'description' => t('При сохранении документов будет оставлено указанное количество архивных ревизий, все остальные будут удалены.'),
      )));

    return $form;
  }

  /**
   * Сборщик мусора.
   *
   * При удалении документов удаляет информацию о ревизии, связях и доступе к
   * удаляемому объекту.  Это позволяет отказаться от требования InnoDB и других
   * типов БД, занимающихся каскадным удалением автоматически.
   *
   * TODO: вынести в BaseGarbageCollector, может?
   *
   * @return void
   */
  public static function hookNodeUpdate(Node $node, $op)
  {
    switch ($op) {
    case 'erase':
      // Удаляем расширенные данные.
      $t = new TableInfo('node_'. $node->class);
      if ($t->exists())
        mcms::db()->exec("DELETE FROM `node_{$node->class}` WHERE `rid` IN (SELECT `rid` FROM `node__rev` WHERE `nid` = :nid)", array(':nid' => $node->id));

      // Удаляем все ревизии.
      mcms::db()->exec("DELETE FROM `node__rev` WHERE `nid` = :nid", array(':nid' => $node->id));

      // Удаляем связи.
      mcms::db()->exec("DELETE FROM `node__rel` WHERE `nid` = :nid OR `tid` = :tid", array(':nid' => $node->id, ':tid' => $node->id));

      // Удаляем доступ.
      mcms::db()->exec("DELETE FROM `node__access` WHERE `nid` = :nid OR `uid` = :uid", array(':nid' => $node->id, ':uid' => $node->id));

      // Удаление статистики.
      $t = new TableInfo('node__astat');
      if ($t->exists())
        mcms::db()->exec("DELETE FROM `node__astat` WHERE `nid` = :nid", array(':nid' => $node->id));

      break;
    }
  }

  /**
   * Обработка инсталляции модуля.
   *
   * Ничего не делает, просто заглушка — iModuleConfig требует реализации.
   *
   * TODO: вынести в BaseModuleSettings.
   *
   * @return void
   */
  public static function hookPostInstall()
  {
  }
};
