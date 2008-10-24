<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeIndexer
{
  public static function stats()
  {
    static $stat = null;

    if (null === $stat) {
      $stat = array(
        '_total' => 0,
        );

      mcms::db()->log('-- node indexer starts --');

      $types = Node::find(array(
        'class' => 'type',
        'deleted' => 0,
        ));

      foreach ($types as $meta) {
        $type = $meta->name;
        $indexed = false;
        $reserved = TypeNode::getReservedNames();

        $schema = Node::create($meta->name)->schema();

        foreach ($schema['fields'] as $k => $v) {
          if (!empty($v['indexed']) and !in_array($k, $reserved)) {
            $indexed = true;
            break;
          }
        }

        if ($indexed) {
          self::countTable($type, $stat, $schema);
        }
      }
    }

    return empty($stat['_total']) ? null : $stat;
  }

  private static function countTable($type, array &$stat, array $schema)
  {
    $table = 'node__idx_'. $type;

    try {
      $sql = "SELECT COUNT(*) FROM `node` `n` "
        ."WHERE `n`.`class` = '{$type}' "
        ."AND `n`.`deleted` = 0 "
        ."AND NOT EXISTS (SELECT 1 FROM `{$table}` `n1` "
        ."WHERE `n1`.`id` = `n`.`id`)";

      if ($count = mcms::db()->getResult($sql)) {
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
        $ids = mcms::db()->getResultsV('id', "SELECT `n`.`id` FROM `node` `n` "
          ."WHERE `n`.`deleted` = 0 AND `n`.`class` = ? AND NOT EXISTS "
          ."(SELECT 1 FROM `node__idx_{$class}` `i` WHERE `i`.`id` = `n`.`id`) "
          ."LIMIT 50", array($class));

        $nodes = Node::find(array(
          'id' => $ids,
          '#recurse' => 1,
          ));

        foreach ($nodes as $n)
          $n->reindex();

        $repeat = true;
      }
    }

    return $repeat;
  }
}
