<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeIndexer
{
  public static function stats($cache = true)
  {
    static $stat = null;

    if (null === $stat) {
      if (!$cache or (false === ($stat = mcms::cache('nodeindexer:stats')))) {
        $stat = array(
          '_total' => 0,
          );

        mcms::db()->log('-- node indexer starts --');

        $types = mcms::db()->getResultsV("name", "SELECT v.name AS name FROM node__rev v "
          . "INNER JOIN node n ON n.rid = v.rid WHERE n.class = 'type' AND n.deleted = 0");

        foreach ($types as $type) {
          $schema = Node::create($type)->schema();
          if ($schema->hasIndexes())
            self::countTable($type, $stat, $schema);
        }

        mcms::cache('nodeindexer:stats', $stat);
      }
    }

    return empty($stat['_total']) ? null : $stat;
  }

  private static function countTable($type, array &$stat, Schema $schema)
  {
    $table = 'node__idx_'. $type;

    mcms::flog('indexer', $type . ': counting, indexes: ' . join(', ', $schema->getIndexes()) . '.');

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

    mcms::flog('indexer', sprintf('%s: %d nodes not indexed.', $type, $stat[$type]));
  }

  public static function run()
  {
    if (null !== ($stat = self::stats(false))) {
      $ctx = Context::last();

      if (null !== ($class = $ctx->get('class'))) {
        if (!empty($stat[$class])) {
          $ctx->db->beginTransaction();

          $ids = $ctx->db->getResultsV('id', "SELECT `n`.`id` FROM `node` `n` "
            ."WHERE `n`.`deleted` = 0 AND `n`.`class` = ? AND NOT EXISTS "
            ."(SELECT 1 FROM `node__idx_{$class}` `i` WHERE `i`.`id` = `n`.`id`) "
            ."LIMIT 10", array($class));

          while (!empty($ids)) {
            $node = Node::load(array(
              'id' => array_shift($ids),
              '#recurse' => 1,
              ));

            $node->reindex();

            $stat[$class]--;
          }

          $ctx->db->commit();
        }
      }

      $class = array_pop(array_keys($stat));

      $url = '?q=admin.rpc&action=reindex'
        . '&class=' . $class
        . '&left=' . $stat[$class];

      $ctx->redirect($url);
    }
  }
}
