<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class User
{
  private $node;
  private $groups;
  private $access;
  private $ctx;
  private $load;

  // Базовая инициализация объекта. Ничего не делает.
  public function __construct(Context $ctx)
  {
    $this->ctx = $ctx;
    $this->reset();
  }

  /**
   * Сбрасывает всю информацию.
   */
  protected function reset()
  {
    $this->node = null;
    $this->groups = null;
    $this->access = null;
    $this->load = true;
  }

  /**
   * Проверка наличия у пользователя нужных прав.
   */
  public function hasAccess($mode, $type)
  {
    $map = $this->loadAccess();

    if (isset($map[$type][0]))
      return $map[$type][0] & $mode;
    else
      return false;
  }

  /**
   * Проверка наличия у пользователя нужных прав.
   * При их отсутствии кидает исключение.
   */
  public function checkAccess($mode, $type)
  {
    if (!$this->hasAccess($mode, $type))
      throw new ForbiddenException();
  }

  /**
   * Возвращает список типов, к которым у пользователя есть доступ.
   */
  public function getAccess($mode = ACL::READ)
  {
    $result = array();
    foreach ($this->loadAccess() as $type => $info)
      if (isset($info[0]) and $info[0] & $mode)
        $result[] = $type;
    return $result;
  }

  /**
   * Загрузка информации о правах пользователя.
   */
  private function loadAccess()
  {
    if (null === $this->access)
      $this->access = ACL::getTypeAccess($this->getGroups());
    return $this->access;
  }

  /**
   * Вход с проверкой пароля.
   * При возникновении ошибки кидает исключение.
   */
  public function login($name, $password, $skipPasswordCheck = false)
  {
    try {
      $node = Node::load(array(
        'class' => 'user',
        'deleted' => 0,
        'name' => $name,
        ));
    } catch (ObjectNotFoundException $e) {
      throw new ForbiddenException(t('Нет такого пользователя.'));
    }

    if (!$skipPasswordCheck) {
      if (!empty($node->password) and md5($password) != $node->password)
        throw new ForbiddenException(t('Неверный пароль.'));
    }

    $this->node = $node;

    self::storeSessionData($node->id);
  }

  /**
   * Сохраняет информацию о пользователе в сессии.
   */
  public static function storeSessionData($userid)
  {
    $groups = $userid
      ? Context::last()->db->getResultsV("id", "SELECT `id`, `name` FROM `node` WHERE `deleted` = 0 AND `published` = 1 AND `class` = 'group' AND `id` IN (SELECT `tid` FROM `node__rel` WHERE `nid` = ?)", array($userid))
      : null;
    mcms::session('uid', $userid);
    mcms::session('groups', $groups);
    mcms::session()->save();
  }

  /**
   * Выход.
   */
  public function logout()
  {
    self::storeSessionData(null);
    $this->reset();
  }

  /**
   * Обращение к свойствам профиля.
   * Прозрачно загружает пользователя.
   */
  public function __get($key)
  {
    switch ($key) {
    case 'id':
      return mcms::session('uid');
    default:
      throw new InvalidArgumentException(t('У класса %class нет свойства %property.', array(
        '%class' => __CLASS__,
        '%property' => $key,
        )));
    }
  }

  /**
   * Возвращает профиль пользователя.
   */
  public function getNode()
  {
    if ($this->load) {
      try {
        $this->node = Node::load(array(
          'class' => 'user',
          'deleted' => 0,
          'published' => 1,
          'id' => mcms::session('uid'),
          ));
      } catch (ObjectNotFoundException $e) {
      }
      $this->load = false;
    }

    return $this->node;
  }

  /**
   * Возвращает группы, в которых состоит пользователь.
   */
  public function getGroups()
  {
    if (null === $this->groups) {
      $this->groups = (array)mcms::session('groups');
      $this->groups[] = 0;
    }

    return $this->groups;
  }

  /**
   * Возвращает список групп в виде строки, для использования в ключах кэша.
   */
  public function getGroupKeys()
  {
    $list = $this->getGroups();
    asort($list);
    return implode(',', $list);
  }

  /**
   * Проверка наличия пользователя в группах.
   *
   * @param array $ids идентификаторы групп.
   * @return bool true, если пользователь состоит в одной из указанных групп.
   */
  public function hasGroups(array $ids)
  {
    $i = array_intersect($this->getGroups(), $ids);

    return !empty($i);
  }

  /**
   * Получение списка разделов, доступных пользователю.
   */
  public function getPermittedSections()
  {
    $uids = join(", ", $this->getGroups());
    $list = $this->ctx->db->getResultsV("nid", "SELECT nid FROM node__access WHERE uid IN ({$uids}) AND c = 1 AND nid IN (SELECT id FROM node WHERE class = 'tag' AND deleted = 0)");
    return $list;
  }

  /**
   * Возвращает профиль анонимного пользователя.
   */
  public function getAnonymous()
  {
    $user = new User($this->ctx);
    $user->node = Node::create(array(
      'class' => 'user',
      ));
    $user->groups = array(0);

    $user->load = false;
    return $user;
  }
}
