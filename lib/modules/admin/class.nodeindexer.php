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

        $types = mcms::db()->getResultsV("name", "SELECT name FROM node "
          . "WHERE class = 'type' AND deleted = 0");

        foreach ($types as $type) {
          $schema = Schema::load($type);
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
    self::fixRevisions();

    if (null !== ($stat = self::stats(false))) {
      $ctx = Context::last();

      if (array_key_exists('_total', $stat))
        unset($stat['_total']);

      foreach ($stat as $class => $count) {
        $ctx->db->beginTransaction();

        $ids = $ctx->db->getResultsV('id', "SELECT `n`.`id` FROM `node` `n` "
          ."WHERE `n`.`deleted` = 0 AND `n`.`class` = ? AND NOT EXISTS "
          ."(SELECT 1 FROM `node__idx_{$class}` `i` WHERE `i`.`id` = `n`.`id`) ",
          array($class));

        while (!empty($ids)) {
          $id = array_shift($ids);

          try {
            $node = Node::load(array(
              'id' => $id,
              'class' => $class,
              '#recurse' => 1,
              '#cache' => false,
              ));

            $node->reindex();
          } catch (Exception $e) {
            --$count;
            mcms::flog(sprintf('node %u (%s): %s', $id, $class, $e->getMessage()));
          }
        }

        $ctx->db->commit();

        mcms::flog(sprintf('%u nodes of type %s indexed.', $count, $class));
      }
    }
  }

  private static function fixRevisions()
  {
  }
}
