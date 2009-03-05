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

    // mcms::debug($output);

    return parent::wrapXML(array(), $output);
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
      $rows = $data->getDB()->getResultsK("uid", "SELECT uid, c, r, u, d, p FROM node__access WHERE nid = ?", array($id));

      foreach ($names as $id => $name) {
        $row = array_key_exists($id, $rows)
          ? $rows[$id]
          : array();

        $row['id'] = $id ? $id : 'all';
        $row['label'] = $name;

        $result[] = $row;
      }
    }

    if (is_object($data) and 'type' == $data->class) {
      $perms = (array)$data->perm_own;
      $result[] = array(
        'uid' => 0,
        'u' => in_array('u', $perms),
        'd' => in_array('d', $perms),
        'p' => in_array('p', $perms),
        'id' => 'own',
        'label' => t('Собственные объекты'),
        );
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
    if (empty($value['__reset']))
      return;

    unset($value['__reset']);

    // Изменение прав на собственные объекты.
    if (array_key_exists('own', $value)) {
      $own = $value['own'];
      unset($value['own']);
      $node->perm_own = array_keys($own);
    }

    $node->onSave('DELETE FROM `node__access` WHERE `nid` = %ID%');
    foreach ($value as $gid => $modes) {
      $params = array(intval($gid));
      foreach (array('c', 'r', 'u', 'd', 'p') as $mode)
        $params[] = empty($modes[$mode])
          ? 0
          : 1;
      $node->onSave('INSERT INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`, `p`) VALUES (%ID%, ?, ?, ?, ?, ?, ?)', $params);
    }
  }
};
