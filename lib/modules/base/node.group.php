<?php
/**
 * Тип документа "group" — группа пользователей.
 *
 * @package mod_base
 * @subpackage Types
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Тип документа "group" — группа пользователей.
 *
 * @package mod_base
 * @subpackage Types
 */
class GroupNode extends Node implements iContentType
{
  /**
   * Сохранение группы.
   *
   * Проверяет имя группы на уникальность.
   *
   * @return Node сохранённый объект.
   */
  public function save()
  {
    if (empty($this->login))
      $this->login = $this->name;

    parent::checkUnique('name', t('Группа с таким именем уже существует'));

    return parent::save();
  }

  /**
   * Клонирование группы.
   *
   * Добавляет к имени группы немного цифр.
   *
   * @return Node новая группа.
   */
  public function duplicate()
  {
    $this->login = preg_replace('/_[0-9]+$/', '', $this->login) .'_'. ($rand = rand());
    $this->name = preg_replace('/ \([0-9]+\)$/', '', $this->name) .' ('. $rand .')';

    return parent::duplicate();
  }

  /**
   * Сохранение прав на типы документов.
   * @todo где и когда используется?  Описать!
   */
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

  /**
   * Возвращает форму для редактирования группы.
   *
   * Добавляет в форму вкладки со списком пользователей и типов документов, к
   * которым у группы есть доступ.  Динамически меняет заголовок формы (делая
   * его читабельным).
   *
   * @return Form полученная от родителя форма с парой новых вкладок.
   *
   * @param bool $simple передаётся родителю, локально не используется.
   */
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
      'intro' => t('Ниже приведены типы документов, которые эта группа может создавать (C), читать (R), изменять (U), удалять (D) и публиковать (P).  Для быстрого изменения прав можно кликать в заголовок строки или столбца.'),
      ));
    $tab->addControl(new HiddenControl(array(
      'value' => 'reset_group_perm',
      'default' => 1,
      )));
    $tab->addControl(new AccessControl(array(
      'value' => 'perm',
      'options' => $options,
      'label' => t('Права на типы документов'),
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

  /**
   * Возвращает данные для формы.
   *
   * В массив, возвращённый родителями, добавляется информация о правах для
   * редактируемой группы (используется в AccessControl).
   *
   * @return array данные для формы.
   */
  public function formGetData()
  {
    $data = parent::formGetData();
    $data['node_group_users'] = $this->linkListChildren('user', true);

    $data['reset_group_perm'] = 1;

    if (isset($this->id)) {
      $tmp = mcms::db()->getResultsK("name", "SELECT `v`.`name` as `name`, "
        ."`a`.`c` as `c`, `a`.`r` as `r`, `a`.`u` as `u`, `a`.`d` as `d`, `a`.`p` as `p` FROM "
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

  /**
   * Обработка формы.
   *
   * В дополнение к родительским действиям обрабатывает изменения в списке
   * пользователей, прикреплённых к группе, и в правах этой группы.
   *
   * Права изменяются только если POST-параметр "reset_group_perm" не пуст.
   * Это нужно для того, чтобы можно было редактировать группу в "упрощённом
   * режиме" (см. параметр $simple метода Node::formGet()), и при этом права не
   * слетали бы.
   *
   * @return mixed то, что вернул родитель.
   */
  public function formProcess(array $data)
  {
    $res = parent::formProcess($data);

    if (mcms::user()->hasAccess('u', 'group'))
      $this->linkSetChildren(empty($data['node_group_users']) ? array() : $data['node_group_users'], 'user');

    if (!empty($data['reset_group_perm']))
      $this->formProcessPerm($data);

    return $res;
  }

  private function formProcessPerm(array $data)
  {
    mcms::db()->exec("DELETE FROM `node__access` WHERE `uid` = :id", array(':id' => $this->id));

    $schema = TypeNode::getSchema();

    if (!empty($data['perm'])) {
      foreach ($data['perm'] as $k => $v) {
        mcms::db()->exec("REPLACE INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`, `p`) VALUES (:nid, :uid, :c, :r, :u, :d, :p)", array(
          ':nid' => $schema[$k]['id'],
          ':uid' => $this->id,
          ':c' => in_array('c', $v) ? 1 : 0,
          ':r' => in_array('r', $v) ? 1 : 0,
          ':u' => in_array('u', $v) ? 1 : 0,
          ':d' => in_array('d', $v) ? 1 : 0,
          ':p' => in_array('p', $v) ? 1 : 0,
          ));
      }
    }
  }

  /**
   * Возвращает базовое описание группы.
   *
   * Возвращает базовую структуру типа "group".
   *
   * @return array структура типа документа "group".
   *
   * @see TypeNode::getSchema()
   */
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
