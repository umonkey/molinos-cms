<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class User
{
  private $node = null;
  private $groups = array();
  private $access = null;
  private $session = null;

  private static $instance = null;

  protected function __construct(UserNode $node = null)
  {
    if (null !== $node) {
      $this->node = $node;
    }

    elseif (null === ($this->session = SessionData::load()) or null === ($uid = $this->session->uid)) {
      $this->node = Node::create('user', array(
        'name' => 'anonymous',
        ));
      foreach (Node::find(array('class' => 'group')) as $tmp)
        if ($tmp->login == 'nobody')
          $this->groups[] = $tmp;
    } else {
      try {
        if (is_string($tmp = mcms::cache($key = 'userprofile:'. $uid)))
          $this->node = unserialize($tmp);
        else
          mcms::cache($key, serialize($this->node = Node::load(array('class' => 'user', 'id' => $uid))));

        if (is_string($tmp = mcms::cache($key = 'usergroups:'. $uid)) and is_array($tmp = unserialize($tmp) and !empty($tmp)))
          $this->groups = $tmp;
        else {
          mcms::cache($key, serialize($this->groups = Node::find(array('class' => 'group', 'published' => 1, 'tagged' => array($uid)))));
        }
      } catch (ObjectNotFoundException $e) {
        $this->node = Node::create('user', array(
          'name' => 'anonymous',
          ));
      }
    }

    if (null !== $this->node and 'anonymous' != $this->node->name) {
      /*
      mcms::log('auth', t('user=%user, groups=%groups', array(
        '%user' => $this->node->name,
        '%groups' => join(',', array_keys($this->getGroups())),
        )));
      */
    }
  }

  public function hasAccess($mode, $type)
  {
    $map = $this->loadAccess();

    if (array_key_exists($mode, $map) and in_array($type, $map[$mode]))
      return true;

    return false;
  }

  public function checkAccess($mode, $type)
  {
    if (!$this->hasAccess($mode, $type))
      throw new ForbiddenException();
  }

  public function getAccess($mode)
  {
    $map = $this->loadAccess();

    if (array_key_exists($mode, $map))
      return $map[$mode];

    return array();
  }

  // Загружаем информацию о правах.
  private function loadAccess()
  {
    if (null === $this->access) {
      if (empty($this->id) or !count($this->getGroups()))
        $result = $this->loadAnonAccess();
      else
        $result = $this->loadGroupAccess();

      $this->access = $result;
    }

    return $this->access;
  }

  private function loadAnonAccess()
  {
    $sql = "SELECT `v`.`name` AS `name`, "
      ."MAX(`a`.`c`) AS `c`, MAX(`a`.`r`) AS `r`, MAX(`a`.`u`) AS `u`, "
      ."MAX(`a`.`d`) AS `d`, MAX(`a`.`p`) AS `p` "
      ."FROM `node` `n` "
      ."INNER JOIN `node__rev` `v` ON `v`.`rid` = `n`.`rid` "
      ."INNER JOIN `node__access` `a` ON `a`.`nid` = `n`.`id` "
      ."WHERE `n`.`class` = 'type' AND `n`.`deleted` = 0 "
      ."AND `a`.`uid` = 0 GROUP BY `v`.`name`";

    return $this->loadRawAccess($sql);
  }

  private function loadGroupAccess()
  {
    $keys = array_keys($this->getGroups());
    sort($keys);

    $sql = "SELECT `v`.`name` AS `name`, "
      ."MAX(`a`.`c`) AS `c`, MAX(`a`.`r`) AS `r`, MAX(`a`.`u`) AS `u`, "
      ."MAX(`a`.`d`) AS `d`, MAX(`a`.`p`) AS `p` "
      ."FROM `node` `n` "
      ."INNER JOIN `node__rev` `v` ON `v`.`rid` = `n`.`rid` "
      ."INNER JOIN `node__access` `a` ON `a`.`nid` = `n`.`id` "
      ."WHERE `n`.`class` = 'type' AND `n`.`deleted` = 0 "
      ."AND `a`.`uid` IN (". join(', ', $keys) .") GROUP BY `v`.`name`";

    return $this->loadRawAccess($sql);
  }

  // Выполняет запрос, парсит результат в пригодный вид.
  // Использует быстрый кэш для хранения результата.
  private function loadRawAccess($sql)
  {
    $key = 'access:'. md5($sql);

    if (false and is_array($result = mcms::cache($key)))
      return $result;

    $result = array();
    $data = mcms::db()->getResults($sql);
    $mask = array('c', 'r', 'u', 'd', 'p');

    foreach ($data as $row) {
      foreach ($mask as $mode)
        if (!empty($row[$mode]))
          $result[$mode][] = $row['name'];
    }

    mcms::cache($key, $result);

    return $result;
  }

  // ОСНОВНОЙ ИНТЕРФЕЙС

  // Восстановление пользователя из сессии.  Если пользователь не
  // идентифицирован, будет загружен обычный анонимный профиль, без
  // поддержки сессий.
  public static function identify()
  {
    if (array_key_exists('openid_mode', $_GET)) {
      $node = OpenIdProvider::openIDAuthorize($_GET['openid_mode']);
      $sid = md5($openid. microtime() . $_SERVER['HTTP_HOST']);
      unset($_GET['openid_mode']); // чтобы повторно не сваливаться в этот IF

      // Сохраняем сессию в БД.
      SessionData::db($sid, array('uid' => $node->id));
      self::setcookie($sid);
      self::$instance = new User($node);
    }

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
        self::setcookie('');
      }
    }

    elseif (count($args) >= 2) {
      if (strpos($args[0], '@') or false === strpos($args[0], '.')) { //e-mail в качестве логина
        $node = Node::load(array('class' => 'user', 'name' => $args[0]));

        if ($node->password != md5($args[1]) and empty($args[2]))
          throw new ForbiddenException(t('Введён неверный пароль.'));

        if (!$node->published)
          throw new ForbiddenException(t('Ваш профиль заблокирован.'));

        // Создаём уникальный идентификатор сессии.
        $sid = md5($node->login . $node->password . microtime() . $_SERVER['HTTP_HOST']);

        // Сохраняем сессию в БД.
        SessionData::db($sid, array('uid' => $node->id));
        self::setcookie($sid);
        self::$instance = new User($node);
      }

      // Возможно, это не e-mail, а openID.
      else {
        OpenIdProvider::OpenIDVerify($args[0]);
        exit();
      }
    } else {
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

  public function __isset($key)
  {
    if ('session' === $key)
      return null !== $this->session;
    return isset($this->node->$key);
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
    if (!$this->hasGroup($name) and !bebop_skip_checks())
      throw new ForbiddenException();
  }

  public static function setcookie($value)
  {
    $path = mcms::path() .'/';
    $time = time() + 60*60*24*30;
    $name = 'mcmsid';

    setcookie($name, $value, $time, $path);
  }
}
