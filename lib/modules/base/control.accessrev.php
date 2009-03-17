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
class AccessRevControl extends Control
{
  public static function getInfo()
  {
  }

  public function __construct(array $form)
  {
    if (!array_key_exists('columns', $form))
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

      if (!empty($row['nid']))
        $id = $row['nid'];
      elseif (!empty($row['id']))
        $id = $row['id'];
      else
        $id = null;

      $output .= html::em('row', array(
        'id' => $id,
        'name' => $row['label'],
        ), $tmp);
    }

    return parent::wrapXML(array(), $output);
  }

  protected function getWrapperClass()
  {
    return 'access-wrapper';
  }

  protected function getData($data)
  {
    $result = array();

    $names = Node::getSortedList($this->dictionary, 'title');

    if (!empty($names)) {
      $rows = $data->getDB()->getResultsK("nid", "SELECT nid, c, r, u, d, p FROM node__access WHERE uid = ?", array($data->id));

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

  public function set($value, &$node)
  {
    if (empty($value['__reset']))
      return;

    unset($value['__reset']);

    $this->validate($value);

    // Удаляем старые записи.
    $node->onSave("DELETE FROM `node__access` WHERE `uid` = %ID% AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = ?)", array($this->dictionary));

    // Добавляем новые.
    foreach ($value as $nid => $row) {
      $params = array(
        ':nid' => $nid,
        );
      foreach (array('c', 'r', 'u', 'd', 'p') as $k)
        $params[':' . $k] = empty($row[$k]) ? 0 : 1;
      $node->onSave("INSERT INTO `node__access` (`uid`, `nid`, `c`, `r`, `u`, `d`, `p`) "
        . "VALUES (%ID%, :nid, :c, :r, :u, :d, :p)", $params);
    }
  }
};
