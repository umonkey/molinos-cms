<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class User
{
  private $node = null;
  private $groups = array();
  private $session = null;

  private static $instance = null;

  protected function __construct()
  {
    if (null === ($this->session = SessionData::load()) or null === ($uid = $this->session->uid)) {
      $this->node = Node::create('user', array(
        'name' => 'anonymous',
        ));
      $this->groups[] = Node::load(array('class' => 'group', 'login' => 'Visitors'));
    } else {
      if (is_string($tmp = mcms::cache($key = 'userprofile:'. $uid)))
        $this->node = unserialize($tmp);
      else
        mcms::cache($key, serialize($this->node = Node::load(array('class' => 'user', 'id' => $uid))));

      if (is_string($tmp = mcms::cache($key = 'usergroups:'. $uid)) and is_array($tmp = unserialize($tmp) and !empty($tmp)))
        $this->groups = $tmp;
      else {
        mcms::cache($key, serialize($this->groups = Node::find(array('class' => 'group', 'published' => 1, 'tagged' => array($uid)))));
      }
    }
  }

  // ОСНОВНОЙ ИНТЕРФЕЙС

  // Восстановление пользователя из сессии.  Если пользователь не идентифицирован,
  // будет загружен обычный анонимный профиль, без поддержки сессий.
  public static function identify()
  {
    if (null === self::$instance)
      self::$instance = new User();

    return self::$instance;
  }

  // Идентифицирует или разлогинивает пользователя.
  public static function authorize()
  {
    $args = func_get_args();

    if (empty($args)) {
      if (array_key_exists('mcmsid', $_COOKIE)) {
        SessionData::db($_COOKIE['mcmsid'], array());
        setcookie('mcmsid', '');
      }
    }

    elseif (2 == count($args)) {
      $node = Node::load(array('class' => 'user', 'login' => $args[0]));

      if ($node->password != md5($args[1]))
        throw new ValidationException('password', t('Введён неверный пароль.'));

      if (!$node->published)
        throw new ForbiddenException(t('Ваш профиль заблокирован.'));

      // Создаём уникальный идентификатор сессии.
      $sid = md5($node->login . $node->password . time() . $_SERVER['HTTP_HOST']);

      // Сохраняем сессию в БД.
      SessionData::db($sid, array('uid' => $node->id));

      setcookie('mcmsid', $sid, time() + 60*60*24*30);
    }

    else {
      throw new InvalidArgumentException(t('Метод User::authorize() принимает либо два параметра, либо ни одного.'));
    }
  }

  public function __get($key)
  {
    if ('session' === $key) {
      if (null === $this->session)
        throw new ForbiddenException();
      return $this->session;
    }
    return $this->node->$key;
  }

  public function getGroups()
  {
    static $result = null;

    if (null === $result) {
      $result = array();

      foreach ($this->groups as $g)
        $result[$g->id] = $g->login;
    }

    return $result;
  }

  public function hasGroup($name)
  {
    if (bebop_skip_checks())
      return true;

    foreach ($this->groups as $g)
      if ($name == $g->login)
        return true;

    return false;
  }

  public function checkGroup($name)
  {
    if (basename($_SERVER['SCRIPT_NAME']) == 'update.php')
      return;

    if (!$this->hasGroup($name) and !bebop_skip_checks())
      throw new ForbiddenException();
  }
}
