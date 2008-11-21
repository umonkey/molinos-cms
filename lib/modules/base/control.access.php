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
    parent::__construct($form);
  }

  public function getHTML($data)
  {
    $table = $this->getData($data);
    $columns = $this->getColumns();

    $output = '<table class=\'padded highlight\'>'
      . "<label>{$this->label}:</label>";

    if (count($columns) > 1)
      $output .= "<tr><th>&nbsp;</th><th>"
        . join ('</th><th>', $columns) . "</th></tr>";

    foreach ($table as $rec) {
      $row = mcms::html('td', $rec['label']);

      foreach (array_keys($columns) as $col) {
        $ctl = mcms::html('input', array(
          'type' => 'checkbox',
          'name' => "{$this->value}[{$rec['id']}][{$col}]",
          'value' => 1,
          'checked' => empty($rec[$col]) ? null : 'checked',
          ));

        $row .= mcms::html('td', $ctl);
      }

      $output .= mcms::html('tr', $row);
    }

    $output .= '</table>' . mcms::html('input', array(
      'type' => 'hidden',
      'name' => $this->value . '[__reset]',
      'value' => 1,
      ));

    $output = $this->wrapHTML($output, false);

    if ($data->right - $data->left > 1) {
      $ctl = mcms::html('input', array(
        'type' => 'checkbox',
        'value' => 1,
        'name' => $this->value . '[__recurse]',
        ));
      $output .= '<div class=\'control\'>';
      $output .= mcms::html('label', array(
        ), $ctl . t('Применить рекурсивно ко всем дочерним объектам'));
      $output .= mcms::html('div', array(
        'class' => 'note',
        ), t('Это приведёт к сбросу ранее установленных прав на вложенные объекты.'));
      $output .= '</div>';
    }

    return $output;
  }

  protected function getData($data)
  {
    $result = array();
    $names = Node::getSortedList('group');

    $names[0] = t('Все посетители');

    if (!empty($names)) {
      $rows = mcms::db()->getResultsK("uid", "SELECT uid, c, r, u, d, p FROM node__access WHERE nid = ?", array($data->id));

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
