<?php

class TreeBuilder
{
  private $next = 1;

  public function run()
  {
    $db = mcms::db();

    // Загружаем исходные данные.
    $data = $this->load($db);

    // Обрабатываем.
    $data = $this->algo2($data);

    $this->update($db, $data);

    $this->checkunique($data);

    // $this->display($data);
  }

  private function update($db, array $data)
  {
    $db->beginTransaction();
    $db->exec("UPDATE `node` SET `left` = NULL, `right` = NULL");
    $sth = $db->prepare("UPDATE `node` SET `left` = ?, `right` = ? WHERE `id` = ?");

    foreach ($data as $k => $v) {
      try {
        $sth->execute($params = array($v['left'], $v['right'], $k));
      } catch (Exception $e) {
        mcms::fatal($e);
      }
    }

    $db->commit();
  }

  private function algo2(array $source)
  {
    $result = array();
    $this->algo2r($result, $source, 0);
    return $result;
  }

  private function algo2r(array &$result, array &$source, $parent_id)
  {
    foreach ($keys = array_keys($source) as $k) {
      if (array_key_exists($k, $source)) {
        $v = $source[$k];

        if (intval($v['parent_id']) === $parent_id) {
          $result[$k] = $v;
          $this->expand($result, $k);
          unset($source[$k]);
          $this->algo2r($result, $source, $k);
        }
      }
    }
  }

  private function checkunique(array $data)
  {
    $ids = array();

    foreach ($data as $row) {
      if (null !== $row['left'])
        $ids[] = $row['left'];
      if (null !== $row['right'])
      $ids[] = $row['right'];
    }

    if (count($ids) != count(array_unique($ids)))
      throw new RuntimeException(t('Не удалось перестроить дерево объектов: есть неуникальные границы, ошибка в алгоритме.'));
  }

  private function load($db)
  {
    $data = array();

    $rows = $db->getResultsKV("id", "parent_id", "SELECT id, parent_id FROM node "
      . "WHERE (parent_id IS NOT NULL OR id IN (SELECT parent_id FROM node)) ORDER BY `left`");

    foreach ($rows as $k => $v)
      $data[$k] = array(
        'parent_id' => $v,
        'left' => null,
        'right' => null,
        );

    return $data;
  }

  private function expand(array &$data, $id, $span = 0)
  {
    // Корень, раздвигаем границы вселенной.
    if (null === ($parent_id = $data[$id]['parent_id'])) {
      // Нужно добавить новый корневой элемент в дерево.
      if (null === $data[$id]['left']) {
        $left = $this->next;
        $right = $this->next + $span + 1;
        $this->next += $span + 2;
      }
    }

    // Не корень, нужно обращаться к родителю.
    else {
      list($left, $right) = $this->expand($data, $parent_id, $rspan = $span + ($data[$id]['left'] ? 0 : 2));
    }

    if (null === $data[$id]['left']) {
      $data[$id]['left'] = $left;
      $data[$id]['right'] = $right;
    } else {
      $data[$id]['right'] += $span;
    }

    if ($data[$id]['right'] >= $this->next)
      $this->next = $data[$id]['right'] + 1;

    if ($span)
      return array($data[$id]['right'] - $span, $data[$id]['right'] - 1);
  }

  private function display(array $data)
  {
    uasort($data, array($this, 'sortfn'));

    $html = '<html><head><style>table { border-collapse: collapse} td, th { text-align: right; padding: 4px 8px; border: solid 1px gray} .l {text-align:left}</style></head><body>';
    $html .= '<table><tr><th class="l">id</th><th>parent_id</th><th>left</th><th>right</th></tr>';
    foreach ($data as $k => $v) {
      if ($v['left'] and $v['right'])
        $html .= sprintf('<tr><td class="l">%s%d</td><td>%d</td><td>%d</td><td>%d</td></tr>', $this->pad($data, $k), $k, $v['parent_id'], $v['left'], $v['right']);
    }
    $html .= '</table>';

    $html .= '</body></html>';

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Length: ' . strlen($html));
    die($html);
  }

  private function pad(array $data, $id)
  {
    $count = 0;

    while ($parent = $data[$id]['parent_id']) {
      $count++;
      $id = $parent;
    }

    return str_repeat('&nbsp;', $count * 4);
  }

  private function sortfn($a, $b)
  {
    return $a['left'] - $b['left'];
  }
}
