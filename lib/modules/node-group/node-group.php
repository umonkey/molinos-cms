<?php

class GroupNode extends Node implements iContentType
{
  // Проверяем уникальность.
  public function save($clear = true)
  {
    if (empty($this->login))
      $this->login = $this->name;

    if ($this->id === null) {
      if (Node::count(array('class' => 'group', 'login' => $this->login)))
        throw new UserErrorException("Имя занято", 400, "Имя занято", "Группа с логином &laquo;{$this->login}&raquo; уже существует.");

      if (Node::count(array('class' => 'group', 'name' => $this->name)))
        throw new UserErrorException("Имя занято", 400, "Имя занято", "Группа с названием &laquo;{$this->name}&raquo; уже существует.");
    }

    parent::save($clear);
  }

  public function duplicate()
  {
    $this->login = preg_replace('/_[0-9]+$/', '', $this->login) .'_'. ($rand = rand());
    $this->name = preg_replace('/ \([0-9]+\)$/', '', $this->name) .' ('. $rand .')';
    parent::duplicate();
  }

  // Сохранение фиксированных прав.
  public function setAccess(array $perms, $reset = true)
  {
    parent::setAccess(array(
      'User Managers' => array('r', 'u', 'd'),
      'Visitors' => array('r'),
      ), true);
  }

  // Сохранение прав на типы документов.
  private function setTypePermissions(array $perms = null)
  {
    $pdo = PDO_Singleton::getInstance();

    // Удаляем существующие права.
    $pdo->exec("DELETE FROM `node__access` WHERE `uid` = :uid AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = 'type')", array(':uid' => $this->id));

    if (!empty($perms) and is_array($perms)) {
      // Готовим запрос для добавления прав.
      $sth = $pdo->prepare("REPLACE INTO `node__access` (`uid`, `nid`, `c`, `r`, `u`, `d`) SELECT :uid, `id`, :c, :r, :u, :d FROM `node` WHERE `class` = 'type' AND `id` = :nid");

      foreach ($perms as $nid => $args) {
        $params = array(
          ':uid' => $this->id,
          ':nid' => $nid,
          ':c' => empty($args['c']) ? 0 : 1,
          ':r' => empty($args['r']) ? 0 : 1,
          ':u' => empty($args['u']) ? 0 : 1,
          ':d' => empty($args['d']) ? 0 : 1,
          );
        $sth->execute($params);
      }
    }
  }

  // Проверка прав на объект.  Менеджеры пользователей всегда всё могут.
  public function checkPermission($perm)
  {
    if (AuthCore::getInstance()->getUser()->hasGroup('User Managers'))
      return true;
    return NodeBase::checkPermission($perm);
  }

  // РАБОТА С ФОРМАМИ.

  public function formGet($simple = true)
  {
    $form = parent::formGet($simple);

    if (null !== ($tab = $this->formGetUsers()))
      $form->addControl($tab);

    $form->title = (null === $this->id)
      ? t('Добавление новой группы')
      : t('Редактирование группы %login', array('%login' => $this->login));

    return $form;
  }

  private function formGetUsers()
  {
    $options = array();

    foreach (Node::find(array('class' => 'user', '#sort' => array('name' => 'ASC'))) as $u)
      $options[$u->id] = $u->name;

    $tab = new FieldSetControl(array(
      'name' => 'users',
      'label' => t('Пользователи'),
      ));
    $tab->addControl(new SetControl(array(
      'value' => 'node_group_users',
      'label' => t('Пользователи группы'),
      'options' => $options,
      )));

    return $tab;
  }

  public function formGetData()
  {
    $data = parent::formGetData();
    $data['node_group_users'] = $this->linkListChildren('user', true);
    return $data;
  }

  public function formProcess(array $data)
  {
    parent::formProcess($data);

    if (AuthCore::getInstance()->getUser()->hasGroup('User Managers'))
      $this->linkSetChildren(empty($data['node_group_users']) ? array() : $data['node_group_users'], 'user');
  }
};

// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:
