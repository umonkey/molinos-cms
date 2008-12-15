<?php

class TreeBuilder
{
  public function run()
  {
    $db = mcms::db();
    $data = $db->getResultsK("id", "SELECT `id`, `parent_id`, `class`, `deleted`, NULL AS `left`, NULL AS `right` FROM `node` WHERE `parent_id` IS NOT NULL OR `id` IN (SELECT `parent_id` FROM `node` WHERE `parent_id` IS NOT NULL) ORDER BY `class`, `left`");

    $db->beginTransaction();

    $next = 1;

    foreach ($data as $k => $v) {
      if (empty($v['parent_id']))
        continue;

      if (!array_key_exists($v['parent_id'], $data)) {
        if (empty($v['deleted'])) {
          mcms::flog($v['parent_id'] . ': unknown parent id, deleting node ' . $v['id']);
          $db->exec('DELETE FROM `node` WHERE `id` = ?', array($v['id']));
        }
        unset($data[$k]);
        continue;
      }

      // Инициализируем родителя, если нужно.
      if (empty($data[$v['parent_id']]['left'])) {
        $data[$v['parent_id']]['left'] = $next;
        $data[$v['parent_id']]['right'] = $next + 1;
        $next += 2;
      }

      // Добавляем текущую ноду в конец родителя.
      $pright = $data[$v['parent_id']]['right'];
      $this->shift($data, $pright, 2);
      $data[$k]['left'] = $pright;
      $data[$k]['right'] = $pright + 1;
      $next += 2;
    }

    mcms::flog('removing old borders');
    $db->exec("UPDATE `node` SET `left` = NULL, `right` = NULL");

    mcms::flog('setting borders');
    $sth = $db->prepare("UPDATE `node` SET `left` = ?, `right` = ? WHERE `id` = ?");
    foreach ($data as $row)
      $sth->execute(array($row['left'], $row['right'], $row['id']));

    mcms::flog('saving.');
    $db->commit();
  }

  private function shift(array &$data, $mark, $delta)
  {
    foreach ($data as $k => $v) {
      if ($v['left'] >= $mark)
        $v['left'] += $delta;
      if ($v['right'] >= $mark)
        $v['right'] += $delta;
      $data[$k] = $v;
    }
  }
}
