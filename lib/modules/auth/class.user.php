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
    if (null === ($this->session = SessionData::load())) {
      $this->node = Node::create('user', array(
        'name' => 'anonymous',
        ));
    } else {
      $this->node = Node::load(array('class' => 'user', 'id' => $this->session->uid));
      $this->groups = Node::find(array('class' => 'group', 'published' => 1, 'tagged' => array($this->node->id)));
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
      setcookie('mcmsid', '');
    }

    elseif (2 == count($args)) {
      $node = Node::load(array('class' => 'user', 'login' => $args[0]));

      if ($node->password != md5($args[1]))
        throw new ValidationError('password', t('Введён неверный пароль.'));

      if (!$node->published)
        throw new ForbiddenException(t('Ваш профиль заблокирован.'));

      // Создаём уникальный идентификатор сессии.
      $sid = md5($node->login . $node->password . time() . $_SERVER['HTTP_HOST']);

      // Сохраняем сессию в БД.
      self::session($sid, array('uid' => $node->id));

      setcookie('mcmsid', $sid, time() + 60*60*24*30);
    }

    else {
      throw new InvalidArgumentException(t('Метод User::authorize() принимает либо два параметра, либо ни одного.'));
    }
  }

  public function __get($key)
  {
    if ('session' === $key)
      return $this->session();
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

  // Возвращает указатель на сессию текущего пользователя.
  private function session($sid, array $data = null)
  {
    try {
      if (null !== $data) {
        mcms::db()->exec("INSERT INTO `node__session` (`sid`, `created`, `data`) VALUES (:sid, UTC_TIMESTAMP(), :data)", array(
          ':sid' => $sid,
          ':data' => serialize($data),
          ));
      } else {
        $tmp = mcms::db()->getResult("SELECT `data` FROM `node__session` WHERE `sid` = :sid", array(
          ':sid' => $sid,
          ));
        return (null === $tmp) ? array() : unserialize($tmp);
      }
    } catch (PDOException $e) {
      if ('42S02' == $e->getCode()) {
        $t = new TableInfo('node__session');

        if (!$t->exists()) {
          $t->columnSet('sid', array(
            'type' => 'char(32)',
            'required' => true,
            'key' => 'pri',
            ));
          $t->columnSet('created', array(
            'type' => 'datetime',
            'required' => true,
            'key' => 'mul',
            ));
          $t->columnSet('data', array(
            'type' => 'blob',
            'required' => true,
            ));
          $t->commit();

          return $this->session($sid, $data);
        }
      }

      throw $e;
    }
  }
}
