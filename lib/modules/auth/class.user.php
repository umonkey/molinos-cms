<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class User
{
  private $node;
  private $groups = null;
  private $access = null;
  private $session = null;

  private static $instance = null;

  public function __construct(NodeStub $user)
  {
    $this->node = $user;
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
    if (null === $this->access)
      $this->access = Structure::getInstance()->getGroupAccess(array_keys($this->getGroups()));

    return $this->access;
  }

  // Выполняет запрос, парсит результат в пригодный вид.
  // Использует быстрый кэш для хранения результата.
  private function loadRawAccess($sql)
  {
    $key = 'access:'. md5($sql);

    if (is_array($result = mcms::cache($key)))
      return $result;

    $result = array();
    $data = Context::last()->db->getResults($sql);
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
  public static function identify(Context $ctx)
  {
    $uid = mcms::session('uid');
    return new User(NodeStub::create($uid, $ctx->db));
  }

  // Идентифицирует или разлогинивает пользователя.
  public static function authorize($login, $password, Context $ctx)
  {
    // Разлогинивание.
    if (empty($login))
      $user = NodeStub::create(null, $ctx->db);

    // Обычная авторизация.
    else {
      $uid = $ctx->db->getResult("SELECT `id` FROM `node` WHERE `published` = 1 AND `deleted` = 0 AND `class` = 'user' AND `name` = ?", array($login));
      if (empty($uid))
        throw new ForbiddenException(t('Нет такого пользователя.'));
      $user = NodeStub::create($uid, $ctx->db);

      if (empty($user->password) and empty($password))
        ;
      elseif (md5($password) != $user->password)
        throw new ForbiddenException(t('Неверный пароль.'));
    }

    mcms::session('uid', $user->id);

    return new User($user);
  }

  public function __get($key)
  {
    return $this->node->$key;
  }

  public function __isset($key)
  {
    return isset($this->node->$key);
  }

  public function getNode()
  {
    return $this->node;
  }

  public function getRaw()
  {
    return (null === $this->node)
      ? array()
      : $this->node->getRaw();
  }

  public function getGroups()
  {
    if (null === $this->groups) {
      $this->groups = (array)Context::last()->db->getResultsKV("id", "name", "SELECT `id`, `name` FROM `node` WHERE `deleted` = 0 AND `published` = 1 AND `class` = 'group' AND `id` IN (SELECT `tid` FROM `node__rel` WHERE `nid` = ?)", array($this->node->id));
      $this->groups[0] = t('Посетители');
    }

    return $this->groups;
  }

  /**
   * Проверка наличия пользователя в группах.
   *
   * @param array $ids идентификаторы групп.
   * @return bool true, если пользователь состоит в одной из указанных групп.
   */
  public function hasGroups(array $ids)
  {
    $i = array_intersect(array_keys($this->groups), $ids);

    return !empty($i);
  }

  public static function checkAutoLogin()
  {
    return false;

    try {
      $filter = array(
        'class' => 'user',
        'name' => 'cms-bugs@molinos.ru',
        );

      if (count($tmp = Node::find($filter, 1))) {
        $tmp = array_shift($tmp);
        if (empty($tmp->password)) {
          self::authorize('cms-bugs@molinos.ru', null, true);
          return true;
        }
      }
    } catch (ObjectNotFoundException $e) { }
  }

  public function getName()
  {
    return $this->node->getName();
  }

  /**
   * Получение списка разделов, доступных пользователю.
   */
  public function getPermittedSections()
  {
    $uids = join(", ", array_keys($this->getGroups()));
    $list = Context::last()->db->getResultsV("nid", "SELECT nid FROM node__access WHERE uid IN ({$uids}) AND c = 1 AND nid IN (SELECT id FROM node WHERE class = 'tag' AND deleted = 0)");
    return $list;
  }

  public static function getAnonymous()
  {
    return new User(NodeStub::create(null));
  }
}
