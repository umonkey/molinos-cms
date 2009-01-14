<?php
/**
 * Контрол для управления правами.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для управления правами.
 *
 * Входные параметры: value = имя массива с данными, options = соответствие
 * внутренних ключей отображаемым, например: "Content Managers" => "Менеджеры
 * контента", ключи используются для формирования имён чекбоксов.
 *
 * Формат входного массива данных: ключ => (c => ?, r => ?, u => ?, d => ?,
 * p => ?).  Ключи соответствуют ключам параметра options.
 *
 * @package mod_base
 * @subpackage Controls
 */
class AccessControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Таблица для работы с правами'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['columns']))
      $form['columns'] = array('c', 'r', 'u', 'd', 'p');
    parent::__construct($form);
  }

  public function getXML($data)
  {
    $output = '';

    foreach ($this->columns as $c)
      $output .= html::em('column', array(
        'name' => $c,
        'label' => mb_strtoupper($c),
        ));

    foreach ($this->getData($data) as $row) {
      $tmp = '';

      foreach ($this->columns as $c)
        $tmp .= html::em('perm', array(
          'name' => $c,
          'enabled' => empty($row[$c]) ? '' : 'yes',
          ));

      $output .= html::em('row', array(
        'id' => $row['id'],
        'name' => $row['label'],
        ), $tmp);
    }

    return parent::wrapXML(array(), $output);
  }

  protected function getOwnPermissions($data)
  {
    if (!($data instanceof TypeNode))
      return;

    $own = is_array($data->perm_own)
      ? $data->perm_own
      : array();

    $output = html::em('legend', 'Права на собственные объекты');

    $output .= html::em('input', array(
      'type' => 'hidden',
      'name' => $this->value . '[own][__reset]',
      'value' => 1,
      ));

    $ctl = html::em('input', array(
      'type' => 'checkbox',
      'name' => $this->value . '[own][]',
      'value' => 'u',
      'checked' => in_array('u', $own) ? 'checked' : null,
      ));
    $output .= html::em('label', $ctl . 'Изменение');

    $ctl = html::em('input', array(
      'type' => 'checkbox',
      'name' => $this->value . '[own][]',
      'value' => 'd',
      'checked' => in_array('d', $own) ? 'checked' : null,
      ));
    $output .= html::em('label', $ctl . 'Удаление');

    $ctl = html::em('input', array(
      'type' => 'checkbox',
      'name' => $this->value . '[own][]',
      'value' => 'p',
      'checked' => in_array('p', $own) ? 'checked' : null,
      ));
    $output .= html::em('label', $ctl . 'Публикация');

    $output = html::em('fieldset', $output);

    return $output;
  }

  protected function getData($data)
  {
    $result = array();
    $names = Node::getSortedList('group');

    $names[0] = t('Все посетители');

    if (!($id = $data->id))
      if (!($id = $data->parent_id))
        $id = null;

    if (!empty($names)) {
      $rows = mcms::db()->getResultsK("uid", "SELECT uid, c, r, u, d, p FROM node__access WHERE nid = ?", array($id));

      foreach ($names as $id => $name) {
        $row = array_key_exists($id, $rows)
          ? $rows[$id]
          : array();

        $row['id'] = $id;
        $row['label'] = $name;

        $result[] = $row;
      }
    }

    return $result;
  }

  protected function getColumns()
  {
    $all = array(
      'c' => 'C',
      'r' => 'R',
      'u' => 'U',
      'd' => 'D',
      'p' => 'P',
      );

    if (is_array($this->columns))
      foreach ($all as $k => $v)
        if (!in_array($k, $this->columns))
          unset($all[$k]);

    return $all;
  }

  public function set($value, Node &$node)
  {
    // FIXME!!!
    if (!$node->id)
      $node->save();

    if (empty($value['__reset']))
      return;

    unset($value['__reset']);

    // Изменение прав на собственные объекты.
    if (array_key_exists('own', $value)) {
      $own = $value['own'];
      unset($value['own']);

      if (!empty($own['__reset'])) {
        unset($own['__reset']);

        $node->perm_own = $own;
      }
    }

    $ids = array();
    if (empty($value['__recurse'])) {
      $ids[] = $node->id;
    } else {
      unset($value['__recurse']);
      foreach ($node->getChildren('flat') as $c)
        $ids[] = $c['id'];
    }

    if (empty($ids))
      throw new RuntimeException(t('Не удалось получить список объектов для установки прав.'));

    $this->validate($value);

    // Удаляем старые записи.
    mcms::db()->exec("DELETE FROM node__access WHERE nid IN (" . join(", ", $ids) . ") AND (uid = 0 OR uid IN (SELECT id FROM node WHERE class = 'group'))");

    $sth = mcms::db()->prepare("INSERT INTO node__access (uid, nid, c, r, u, d, p) SELECT :uid, id, :c, :r, :u, :d, :p FROM node WHERE id IN (" . join(", ", $ids) . ")");

    foreach ($value as $uid => $row) {
      $params = array(
        ':uid' => intval($uid),
        );

      foreach (array('c', 'r', 'u', 'd', 'p') as $k)
        $params[':' . $k] = empty($row[$k]) ? 0 : 1;

      try {
        $sth->execute($params);
      } catch (PDOException $e) {
        mcms::debug($e->getMessage(), $value, $params);
        throw $e;
      }
    }
  }
};
