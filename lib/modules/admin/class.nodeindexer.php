<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeIndexer
{
  public static function stats()
  {
    $stat = array(
      '_total' => 0,
      );

    foreach (TypeNode::getSchema() as $type => $meta) {
      $indexed = false;
      $reserved = TypeNode::getReservedNames();

      foreach ($meta['fields'] as $k => $v) {
        if (!empty($v['indexed']) and !in_array($k, $reserved)) {
          $indexed = true;
          break;
        }
      }

      if ($indexed)
        self::countTable($type, $stat, $meta);
    }

    return empty($stat['_total']) ? null : $stat;
  }

  private static function countTable($type, array &$stat, array $schema)
  {
    $table = 'node__idx_'. $type;

    try {
      if ($count = mcms::db()->getResult("SELECT COUNT(*) FROM `node` `n` WHERE `n`.`class` = '{$type}' AND `n`.`deleted` = 0 AND NOT EXISTS (SELECT 1 FROM `{$table}` `n1` WHERE `n1`.`id` = `n`.`id`)")) {
        $stat[$type] = $count;
        $stat['_total'] += $count;
      }
    }

    catch (TableNotFoundException $e) {
      if ($table == $e->getTableName()) {
        $node = Node::create('type', $schema);
        $node->updateTable();

        return self::countTable($type, $stat, $schema);
      } else {
        throw $e;
      }
    }
  }

  public static function run()
  {
    $repeat = false;

    if (null !== ($stat = self::stats())) {
      if ('_total' != ($class = array_pop(array_keys($stat)))) {
        $ids = mcms::db()->getResultsV('id', "SELECT `n`.`id` FROM `node` `n` WHERE `n`.`deleted` = 0 AND `n`.`class` = :class AND NOT EXISTS (SELECT 1 FROM `node__idx_{$class}` `i` WHERE `i`.`id` = `n`.`id`) LIMIT 50", array(':class' => $class));

        foreach ($count = Node::find(array('class' => $class, 'id' => $ids)) as $n)
          $n->reindex();

        $repeat = true;
      }
    }

    return $repeat;
  }
}
