<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class User
{
  private $node = null;
  private $groups = array();
  private $access = null;
  private $session = null;

  private static $instance = null;

  public function __construct(UserNode $node = null)
  {
    // Указан конкретный пользователь, работаем с ним.
    if (null !== $node) {
      $this->node = $node;
    }

    // Пользователь не указан, загружаем из сессии.
    elseif ($uid = mcms::session('uid')) {
      try {
        $this->node = Node::load(array(
          'class' => 'user',
          'id' => $uid,
          '#cache' => true,
          ));
      } catch (ObjectNotFoundException $e) {
        // Пользователя удалили — ничего страшного.
      }
    }

    $this->groups[0] = t('Анонимные пользователи');

    // Если пользоватль не найден — делаем анонимным.
    if (null === $this->node) {
      $this->node = Node::create('user', array(
        'name' => 'anonymous',
        ));
    }

    // Если найден — загрузим группы.
    else {
      if (!is_array($groups = mcms::cache("user:{$this->node->id}:groupnames"))) {
        $groups = array();

        $nodes = Node::find(array(
          'class' => 'group',
          'published' => 1,
          'tagged' => array($this->node->id),
          '#recurse' => 0,
          '#files' => false,
          ));

        foreach ($nodes as $k => $v)
          $groups[$k] = $v->getName();

        mcms::cache("user:{$this->node->id}:groupnames", $groups);
      }

      $this->groups += $groups;
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
    if (null === self::$instance)
      self::$instance = new User();

    return self::$instance;
  }

  // Идентифицирует или разлогинивает пользователя.
  public static function authorize()
  {
    $args = func_get_args();

    if (empty($args)) {
      if (null !== mcms::session('uid'))
        mcms::session('uid', null);
    }

    // Авторизация без проверки.
    elseif (count($args) >= 3 and !empty($args[2])) {
      if (!is_object($node = $args[0]))
        $node = Node::load(array(
          'class' => 'user',
          'name' => $args[0],
          ));

      mcms::session('uid', $node->id);
      self::$instance = new User($node);
    }

    elseif (count($args) >= 2) {
      if ((strpos($args[0], '@') or false === strpos($args[0], '.')) or (!empty($args[3]))) { //e-mail в качестве логина или же мы уже прошли процедуры openID-авторищации
        try {
          $node = Node::load($f = array(
            'class' => 'user',
            'name' => $args[0],
            ));
        } catch (ObjectNotFoundException $e) {
          throw new ForbiddenException(t('Пользователь %name не '
            .'зарегистрирован.', array('%name' => $args[0])));
        }

        if (empty($args[2]) and !$node->checkpw($args[1]))
          throw new ForbiddenException(t('Введён неверный пароль.'));

        if (!$node->published)
          throw new ForbiddenException(t('Ваш профиль заблокирован.'));

        mcms::session('uid', $node->id);

        self::$instance = new User($node);
      }

      // Возможно, это не e-mail, а openID.
      else {
        if (!class_exists('OpenIdModule'))
          throw new RuntimeException(t('Модуль OpenID отключен.'));
        OpenIdModule::OpenIDVerify($args[0]);
        exit();
      }
    } else {
      throw new InvalidArgumentException(t('Метод User::authorize() принимает либо два параметра, либо ни одного.'));
    }
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
    $list = mcms::db()->getResultsV("nid", "SELECT nid FROM node__access WHERE uid IN ({$uids}) AND c = 1 AND nid IN (SELECT id FROM node WHERE class = 'tag' AND deleted = 0)");
    return $list;
  }
}
