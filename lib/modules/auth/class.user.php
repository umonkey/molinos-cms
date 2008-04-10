<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class User
{
  private $node = null;
  private $groups = array();
  private $access = null;
  private $session = null;

  private static $instance = null;

  protected function __construct()
  {
    if (null === ($this->session = SessionData::load()) or null === ($uid = $this->session->uid)) {
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
      $keys = array_keys($this->getGroups());
      sort($keys);

      if (is_array($result = mcms::cache($ckey = 'access:'. join(',', $keys))))
        return $this->access = $result;

      $result = array();

      if (count($groups = array_keys($this->getGroups()))) {
        $data = mcms::db()->getResults($sql = "SELECT `v`.`name`, MAX(`a`.`c`) AS `c`, MAX(`a`.`r`) AS `r`, MAX(`a`.`u`) AS `u`, MAX(`a`.`d`) AS `d` FROM `node` `n` INNER JOIN `node__rev` `v` ON `v`.`rid` = `n`.`rid` INNER JOIN `node__access` `a` ON `a`.`nid` = `n`.`id` WHERE `n`.`class` = 'type' AND `n`.`deleted` = 0 AND `a`.`uid` IN (". join(', ', $groups) .") GROUP BY `v`.`name`");
        $mask = array('c', 'r', 'u', 'd');

        foreach ($data as $row) {
          foreach ($mask as $mode)
            if (!empty($row[$mode]))
              $result[$mode][] = $row['name'];
        }
      }

      mcms::cache($ckey, $this->access = $result);
    }

    return $this->access;
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

    elseif (count($args) >= 2) {
      $node = Node::load(array('class' => 'user', 'name' => $args[0]));

      if ($node->password != md5($args[1]) and empty($args[2]))
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
