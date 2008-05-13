<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class GroupNode extends Node implements iContentType
{
  // Проверяем уникальность.
  public function save()
  {
    if (empty($this->login))
      $this->login = $this->name;

    if ($this->id === null) {
      if (Node::count(array('class' => 'group', 'name' => $this->name)))
        throw new UserErrorException("Имя занято", 400, "Имя занято", "Группа с названием &laquo;{$this->name}&raquo; уже существует.");
    }

    parent::save();
  }

  public function duplicate()
  {
    $this->login = preg_replace('/_[0-9]+$/', '', $this->login) .'_'. ($rand = rand());
    $this->name = preg_replace('/ \([0-9]+\)$/', '', $this->name) .' ('. $rand .')';
    parent::duplicate();
  }

  // Сохранение прав на типы документов.
  private function setTypePermissions(array $perms = null)
  {
    $pdo = mcms::db();

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

  // РАБОТА С ФОРМАМИ.

  public function formGet($simple = true)
  {
    $form = parent::formGet($simple);

    if (null !== ($tab = $this->formGetUsers()))
      $form->addControl($tab);

    if (null !== ($tab = $this->formGetTypes()))
      $form->addControl($tab);

    $form->title = (null === $this->id)
      ? t('Добавление новой группы')
      : t('Редактирование группы %name', array('%name' => $this->name));

    return $form;
  }

  private function formGetTypes()
  {
    $options = array();

    foreach (TypeNode::getSchema() as $k => $v)
      $options[$k] = empty($v['title']) ? $k : $v['title'];

    asort($options);

    $tab = new FieldSetControl(array(
      'name' => 'tab_perm',
      'label' => t('Права'),
      'intro' => t('Ниже приведены типы документов, которые эта группа может создавать (C), читать (R), изменять (U) и удалять (D).  Для быстрого изменения прав можно кликать в заголовок строки или столбца.'),
      ));
    $tab->addControl(new HiddenControl(array(
      'value' => 'reset_group_perm',
      )));
    $tab->addControl(new AccessControl(array(
      'value' => 'perm',
      'options' => $options,
      )));

    return $tab;
  }

  private function formGetUsers()
  {
    $options = array();

    foreach ($nodes = Node::find(array('class' => 'user', '#sort' => array('name' => 'ASC'))) as $u)
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

    $data['reset_group_perm'] = 1;

    if (isset($this->id)) {
      $tmp = mcms::db()->getResultsK("name", "SELECT `v`.`name` as `name`, "
        ."`a`.`c` as `c`, `a`.`r` as `r`, `a`.`u` as `u`, `a`.`d` as `d` FROM "
        ."`node` `n` INNER JOIN `node__rev` `v` ON `v`.`rid` = `n`.`rid` "
        ."INNER JOIN `node__access` `a` ON `a`.`nid` = `n`.`id` "
        ."WHERE `n`.`class` = 'type' AND `n`.`deleted` = 0 AND `a`.`uid` = :id", array(
          ':id' => $this->id,
          ));

      foreach ($tmp as $k => $v) {
        unset($v['name']);
        $data['perm'][$k] = $v;
      }
    }

    return $data;
  }

  public function formProcess(array $data)
  {
    parent::formProcess($data);

    if (mcms::user()->hasAccess('u', 'group'))
      $this->linkSetChildren(empty($data['node_group_users']) ? array() : $data['node_group_users'], 'user');

    if (!empty($data['reset_group_perm']))
      $this->formProcessPerm($data);
  }

  private function formProcessPerm(array $data)
  {
    mcms::db()->exec("DELETE FROM `node__access` WHERE `uid` = :id", array(':id' => $this->id));

    $schema = TypeNode::getSchema();

    if (!empty($data['perm'])) {
      foreach ($data['perm'] as $k => $v) {
        mcms::db()->exec("REPLACE INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`) VALUES (:nid, :uid, :c, :r, :u, :d)", array(
          ':nid' => $schema[$k]['id'],
          ':uid' => $this->id,
          ':c' => in_array('c', $v) ? 1 : 0,
          ':r' => in_array('r', $v) ? 1 : 0,
          ':u' => in_array('u', $v) ? 1 : 0,
          ':d' => in_array('d', $v) ? 1 : 0,
          ));
      }
    }
  }

  public function getDefaultSchema()
  {
    return array(
      'description' => 'Используется для управления правами.',
      'title' => 'Группа пользователей',
      'notags' => true,
      'fields' => array (
        'name' => array (
          'label' => 'Название',
          'type' => 'TextLineControl',
          'required' => true,
          ),
        'description' => array (
          'label' => 'Описание',
          'type' => 'TextAreaControl',
          ),
        ),
      );
  }
}
