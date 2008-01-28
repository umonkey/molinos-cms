<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class User
{
  private $uid;
  private $name;
  private $title;
  private $password;
  private $groups = null;
  private $systemgroups = null;

  protected function __construct($uid, $name, $password = null, $title = null)
  {
    $this->uid = $uid;
    $this->name = $name;
    $this->password = $password;
    $this->title = empty($title) ? $name : $title;
  }

  public static function restore(array $data)
  {
    $defaults = array(
      'uid' => 0,
      'name' => 'anonymous',
      'groups' => array('Visitors'),
      );

    $data = array_merge($defaults, $data);

    if ($data['name'] != 'anonymous') {
      $user = self::authorize($data['name'], null, true);
    } else {
      $user = new User($data['uid'], $data['name'], null, @$data['title']);
      $user->groups = $data['groups'];
      $user->systemgroups = @$data['systemgroups'];
    }

    return $user;
  }

  public function store()
  {
    bebop_session_start();

    $_SESSION['user'] = array(
      'uid' => $this->uid,
      'name' => $this->name,
      'title' => empty($this->title) ? $this->name : $this->title,
      'groups' => $this->groups,
      'systemgroups' => $this->systemgroups,
      );

    bebop_session_end();
  }

  static private function anonymize()
  {
    return array(
      'id' => 0,
      'name' => 'anonymous',
      'title' => 'anonymous',
      'password' => '',
      );
  }

  // factory
  static public final function authorize($name = 'anonymous', $pass = null, $bypass = false)
  {
    $data = null;

    if ($name != 'anonymous') {
      $errormsg = t("Ошибка в логине или пароле, попробуйте ещё раз.");

      PDO_Singleton::getInstance()->log("--- user {$name} auth ---");

      try {
        $nodes = Node::find(array('class' => 'user', 'login' => $name));

        if (count($nodes) != 1)
          throw new ForbiddenException($errormsg);

        $node = array_pop($nodes);

        if (empty($node->published))
          throw new ForbiddenException(t('Ваш профиль не активирован.&nbsp; Проверьте почту и следуйте содержащимся в ней инструкциям.'));

        if (!$bypass and $node->password != md5($pass))
          throw new ForbiddenException($errormsg);

        $data = array(
          'id' => $node->id,
          'name' => $node->login,
          'title' => $node->name,
          'password' => '',
          );
      } catch (PDOException $e) { }
    }

    if (null === $data)
      $data = self::anonymize();

    $user = new User($data['id'], $data['name'], $data['password'], @$data['title']);
    $user->loadGroups();
    $user->store();

    return $user;
  }

  public function getName()
  {
    return $this->name;
  }

  public function getTitle()
  {
    return empty($this->title) ? $this->name : $this->title;
  }

  public function getUid()
  {
    return $this->uid;
  }

  private function loadGroups()
  {
    try {
      if ($this->groups == null and $this->systemgroups == null) {
        $rows = PDO_Singleton::getInstance()->getResults(
          "SELECT `n`.`id`, `g`.`login`, `g`.`system` "
          ."FROM `node` `n` "
          ."INNER JOIN `node_group` `g` ON `g`.`rid` = `n`.`rid` "
          ."WHERE `n`.`lang` = 'ru' "
          ."AND `n`.`class` = 'group' "
          ."AND `n`.`id` IN (SELECT `r`.`tid` FROM `node__rel` `r` INNER JOIN `node` ON `node`.`id` = `r`.`nid` INNER JOIN `node_user` ON `node_user`.`rid` = `node`.`rid` WHERE `node_user`.`login` = :login) -- User::loadGroups({$this->name})",
          array(':login' => $this->name));

        foreach ($rows as $row) {
          if (empty($row['system']))
            $this->groups[$row['id']] = $row['login'];
          else
            $this->systemgroups[$row['id']] = $row['login'];
        }
      }
    }

    // Пропускаем ошибки при загрузке групп анонимного пользователя
    // во время инсталляции, когда таблиц ещё нет.
    catch (Exception $e) {
      if ($this->name != 'anonymous' or !bebop_skip_checks())
        throw $e;
    }
  }

  public function getGroups($system = false)
  {
    $this->loadGroups();

    $result = (array)$this->groups;

    if ($system and $this->systemgroups !== null)
      $result += $this->systemgroups;

    return $result;
  }

  public function hasGroup($name)
  {
    if (bebop_skip_checks())
      return true;
    return in_array($name, $this->getGroups(true));
  }

  public function checkGroup($name)
  {
    if (basename($_SERVER['SCRIPT_NAME']) == 'update.php')
      return;

    if (!$this->hasGroup($name) and !bebop_skip_checks())
      throw new ForbiddenException();
  }
}
