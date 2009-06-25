<?php

class TreeBuilder
{
  /**
   * Шедулер.  Проверяет целостность дерева, запускает обновление.
   * @mcms_message ru.molinos.cms.cron
   */
  public static function on_task_run(Context $ctx)
  {
    if ($message = self::checkBrokenTrees($ctx)) {
      Logger::log("Node tree structure is damaged: {$message}, fixing.");

      $db = $ctx->db;
      $old = $data = $db->getResultsK("id", "SELECT `id`, `parent_id`, `class`, `deleted`, NULL AS `left`, NULL AS `right` "
        . "FROM `node` WHERE `parent_id` IS NOT NULL OR `id` IN (SELECT `parent_id` FROM `node` WHERE `parent_id` IS NOT NULL) "
        . "ORDER BY `class`, `left`");

      $db->beginTransaction();

      $next = 1;

      foreach ($data as $k => $v) {
        if (empty($v['parent_id']))
          continue;

        if (!array_key_exists($v['parent_id'], $data)) {
          if (empty($v['deleted'])) {
            Logger::log($v['parent_id'] . ': unknown parent id, deleting node ' . $v['id']);
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
        self::shift($data, $pright, 2);
        $data[$k]['left'] = $pright;
        $data[$k]['right'] = $pright + 1;
        $next += 2;
      }

      Logger::log('Checking tree structure.');

      $db->exec("UPDATE `node` SET `left` = NULL, `right` = NULL");
      $sth = $db->prepare("UPDATE `node` SET `left` = ?, `right` = ? WHERE `id` = ?");
      foreach ($data as $row)
        $sth->execute(array($row['left'], $row['right'], $row['id']));

      $db->commit();
    }
  }

  /**
   * Проверяет целостность дерева.
   *
   * Ошибкой считается выход левой границы объекта за правую или повторное использование границы.
   */
  private static function checkBrokenTrees(Context $ctx)
  {
    if ($count = $ctx->db->fetch("SELECT COUNT(*) FROM `node` `n` WHERE `n`.`deleted` = 0 AND `n`.`left` >= `n`.`right`"))
      return "left > right";

    if ($ctx->db->fetch("SELECT COUNT(*) AS `c` FROM `node` WHERE `left` IS NOT NULL GROUP BY `left` HAVING `c` > 1"))
      return "duplicate left";

    if ($ctx->db->fetch("SELECT COUNT(*) AS `c` FROM `node` WHERE `right` IS NOT NULL GROUP BY `right` HAVING `c` > 1"))
      return "duplicate right";

    return false;
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
