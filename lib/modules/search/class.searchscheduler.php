<?php

class SearchScheduler implements iScheduler
{
  public static function taskRun()
  {
    // 1. Проиндексировать документы, отсутствующие в индексе.
    // 2. Удалить скрытые и удалённые.
    // 3. Всё остальное нужно делать по hookNode().
    $nids = mcms::db()->getResultsV("id", "SELECT `id` FROM `node` "
      ."WHERE `deleted` = 0 AND `published` = 1 "
      ."AND `class` NOT IN ('". join("', '", TypeNode::getInternal()) ."') "
      ."AND `id` NOT IN (SELECT `nid` FROM `node__searchindex`)");

    /*
    foreach (Node::find(array('id' => $nids), 100) as $node)
      self::reindexNode($node);
    */
  }
}
