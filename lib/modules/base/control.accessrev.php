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
    return array(
      'name' => t('Таблица для работы с правами'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form);
  }

  public function getHTML($data)
  {
    if (($data instanceof Node) and !$data->id)
      return;

    $table = $this->getData($data);
    $columns = $this->getColumns();

    $output = '<table class=\'padded highlight\'>'
      . "<label>{$this->label}:</label>";

    if (count($columns) > 1)
      $output .= "<tr><th>&nbsp;</th><th>"
        . join ('</th><th>', $columns) . "</th></tr>";

    foreach ($table as $rec) {
      $row = html::em('td', $rec['label']);

      foreach (array_keys($columns) as $col) {
        $ctl = html::em('input', array(
          'type' => 'checkbox',
          'name' => "{$this->value}[{$rec['id']}][{$col}]",
          'value' => 1,
          'checked' => empty($rec[$col]) ? null : 'checked',
          'class' => 'perm-' . $col,
          ));

        $row .= html::em('td', $ctl);
      }

      $output .= html::em('tr', $row);
    }

    $output .= '</table>' . html::em('input', array(
      'type' => 'hidden',
      'name' => $this->value . '[__reset]',
      'value' => 1,
      ));

    $output = $this->wrapHTML($output, false);

    return $output;
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
      $rows = mcms::db()->getResultsK("nid", "SELECT nid, c, r, u, d, p FROM node__access WHERE uid = ? AND nid IN (SELECT id FROM node WHERE deleted = 0 AND class = ?)", array($data->id, $this->dictionary));

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
    if (empty($value['__reset']))
      return;

    unset($value['__reset']);

    $this->validate($value);

    // Удаляем старые записи.
    mcms::db()->exec("DELETE FROM node__access WHERE uid = ? AND nid IN (SELECT id FROM node WHERE class = ?)",
      array($node->id, $this->dictionary));

    $sth = mcms::db()->prepare("INSERT INTO node__access (uid, nid, c, r, u, d, p) VALUES (:uid, :nid, :c, :r, :u, :d, :p)");

    foreach ($value as $nid => $row) {
      $params = array(
        ':uid' => intval($node->id),
        ':nid' => $nid,
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
