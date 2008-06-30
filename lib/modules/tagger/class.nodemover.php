<?php

class NodeMover
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function moveUp($nid)
  {
    // Загрузим себя.
    $g1 = $this->db->getResult("SELECT `left`, `right`, "
      ."`parent_id` FROM `node` WHERE `id` = ?", array($nid));

    // Загрузим верхнего соседа.
    $g2 = $this->db->getResult("SELECT `left`, `right` FROM `node` "
      ."WHERE `parent_id` = ? AND `deleted` = 0 AND `right` < ? "
      ."ORDER BY `right` DESC", array($g1['parent_id'], $g1['left']));

    $this->swap($g1, $g2);
  }

  public function moveDown($nid)
  {
    // Загрузим себя.
    $g1 = $this->db->getResult("SELECT `left`, `right`, "
      ."`parent_id` FROM `node` WHERE `id` = ?", array($nid));

    // Загрузим нижнего соседа.
    $g2 = $this->db->getResult("SELECT `left`, `right` FROM `node` "
      ."WHERE `parent_id` = ? AND `deleted` = 0 AND `left` > ? "
      ."ORDER BY `left` ASC", array($g1['parent_id'], $g1['right']));

    $this->swap($g1, $g2);
  }

  private function swap($n1, $n2)
  {
    if (empty($n1) or empty($n2))
      return;

    // Нормализуем порядок блоков.
    $this->nodeFixOrder($n1, $n2);

    // Граница вселенной.
    $end = $this->db->getResult("SELECT MAX(`right`) FROM `node`") + 1;

    // Считаем желаемые позиции.
    $this->calcPositions($n1, $n2, $end);

    // Общая граница блоков.
    $al = min($n1['left'], $n2['left']);
    $ar = max($n1['right'], $n2['right']);

    if (!bebop_is_debugger())
      die('Попробуйте позже.');

    // Переместим всё в конец.
    $delta = $end - $al;
    $this->db->exec("UPDATE `node` SET `left` = `left` + ?, "
      ."`right` = `right` + ? WHERE `left` >= ? AND `right` <= ?",
      array($delta, $delta, $al, $ar));

    // Переносим куда надо.
    $this->moveOneBlock($n2);
    $this->moveOneBlock($n1);
  }

  private function moveOneBlock(array $block)
  {
    $delta = $block['tmp'] - $block['want'];
    $this->db->exec("UPDATE `node` SET `left` = `left` - ?, "
      ."`right` = `right` - ? WHERE `left` >= ? AND `right` <= ?",
      array($delta, $delta, $block['tmp'], $block['tmp'] + $block['size'] - 1));
  }

  private function nodeFixOrder(array &$n1, array &$n2)
  {
    if ($n1['left'] > $n2['left']) {
      $tmp = $n2;
      $n2 = $n1;
      $n1 = $tmp;
    }

    $n2['left'] = $n1['right'] + 1;
  }

  private function getTail()
  {
    return $this->db->getResult("SELECT MAX(`right`) FROM `node`");
  }

  private function calcPositions(array &$n1, array &$n2, $end)
  {
    // Размеры блоков.
    $n1['size'] = $n1['right'] - $n1['left'] + 1;
    $n2['size'] = $n2['right'] - $n2['left'] + 1;

    // Желаемые позиции.
    $n1['want'] = $n1['left'] + $n2['size'];
    $n2['want'] = $n1['left'];

    // Временные позиции (в хвосте).
    $n1['tmp'] = $end;
    $n2['tmp'] = $end + $n1['size'];
  }
}
