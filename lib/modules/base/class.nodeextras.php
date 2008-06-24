<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class NodeExtras
{
  const MOVE_UP = 1;
  const MOVE_DOWN = 2;

  // Интерфейсная часть.

  public function moveUp($id)
  {
    return self::move($id, self::MOVE_UP);
  }

  public function moveDown($id)
  {
    return self::move($id, self::MOVE_DOWN);
  }

  // Внутренняя реализация.

  private function move($id, $direction)
  {
    $node = Node::load($id);
    $node = $node->getRaw();

    $parent_id = $node['parent_id'];
    $left = $node['left'];
    $right = $node['right'];
    $nodeSize = ($right - $left) + 1;

    // Получаем все вложения ноды, которую перемещаем
    $nestedNodes = $this->getNestedNodes($left, $right);

    // Если двигаем наверх - получаем массив соседей слева
    if (self::MOVE_UP == $direction) {
      $neighbourNode = $this->getNeighbourLeft($parent_id, ($left - 1));
      $operand1 = '-';
      $operand2 = '+';
    }

    // Если двигаем вниз - получаем массив соседей справа
    elseif (self::MOVE_DOWN == $direction) {
      $neighbourNode = $this->getNeighbourRight($parent_id, ($right + 1));
      $operand1 = '+';
      $operand2 = '-';
    }

    // Нет соседей - нет перемещения
    if (0 == sizeof($neighbourNode))
        return null;

    // Нужно получить смещение, на которое будут временно перемещены
    // движимые ноды, чтобы не получить ошибку дублирующихся ключей
    $offset = $this->getOffset();

    $neighbourRight = $neighbourNode->right;
    $neighbourLeft = $neighbourNode->left;
    $neighbourSize = ($neighbourRight - $neighbourLeft) + 1;

    // Получаем все вложения
    $nestedNodesNeighbour = $this->getNestedNodes
      ($neighbourLeft, $neighbourRight);

    // Получаем идентификаторы вложений
    foreach($nestedNodes as $k => $v)
      $nestedID[] = $nestedNodes[$k]->id;

    // Переносим первую партию нод с учетом смещения
    $this->orderedUpdate("UPDATE `node` SET `left` = `left` + {$offset}, "
      ."`right` = `right` + {$offset} "
      ."WHERE id IN (" . join(',', $nestedID) . ")", "`left` DESC");

    // Получаем идентификаторы вложений соседа
    foreach ($nestedNodesNeighbour as $k => $v)
      $nestedNeighbourID[] = $nestedNodesNeighbour[$k]->id;

    // Переносим партию нод соседа с учетом смещения
    $this->orderedUpdate("UPDATE `node` SET `left` = `left` + {$offset}, "
      ."`right` = `right` + {$offset} "
      ."WHERE id IN (" . join(',', $nestedNeighbourID) . ")",
      "`left` DESC");

    // Вертаем взад первую партию нод с учетом смещения и размера соседа
    $this->orderedUpdate("UPDATE `node` SET `left` = `left` - {$offset} {$operand1} "
      ."{$neighbourSize}, `right` = `right` - {$offset} {$operand1} "
      ."{$neighbourSize} WHERE id IN (" . join(',', $nestedID) . ")",
      "`left` ASC");

    // Вертаем взад партию нод соседа с учетом смещения
    // и размера первой партии нод
    $this->orderedUpdate("UPDATE `node` SET `left` = `left` - {$offset} {$operand2} "
      ."{$nodeSize}, `right` = `right` - {$offset} {$operand2} "
      ."{$nodeSize} WHERE id IN (" . join(',', $nestedNeighbourID) . ")",
      "`left` ASC");

    return true;
  }

  private function orderedUpdate($sql, $order)
  {
    if (mcms::db()->hasOrderedUpdates())
      $sql .= ' ORDER BY '. $order;
    return mcms::db()->exec($sql);
  }

  private function getNeighbourLeft($parent_id, $index)
  {
    return $this->getNeighbour($parent_id, $index, '<');
  }

  private function getNeighbourRight($parent_id, $index)
  {
    return $this->getNeighbour($parent_id, $index, '>');
  }

  private function getNeighbour($parent_id, $index, $direction)
  {
    if ('<' == $direction) {
        $sort = 'DESC';
        $side = 'right';
    } else {
        $sort = 'ASC';
        $side = 'left';
    }

    $sql = "SELECT * FROM node WHERE class = 'tag' AND parent_id = :parent_id "
      ."AND `{$side}` {$direction}= :index AND `deleted` = 0 "
      ."ORDER BY `{$side}` {$sort} LIMIT 1";

    $params = array(
      'parent_id' => $parent_id,
      'index' => $index,
      );

    $data = NodeBase::dbRead($sql, $params);

    foreach ($data as $k => $v)
      return $v;
  }

  private function getNestedNodes($left, $right)
  {
    $sql = "SELECT * FROM node WHERE class = 'tag' "
      ."AND `left` >= :left "
      ."AND `right` <= :right "
      ."ORDER BY `left` ASC";
    $params = array('left' => $left, 'right' => $right);

    $data = NodeBase::dbRead($sql, $params);

    return $data;
  }

  private function getOffset()
  {
    $sql = "SELECT MAX(`right`) * 2 + 10 AS `offset` FROM `node`";
    $res = mcms::db()->getResult($sql);

    return intval($res);
  }
}
