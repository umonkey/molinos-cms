<?php

class SearchScheduler implements iScheduler
{
  public static function taskRun(Context $ctx)
  {
    $limit = empty($_SERVER['REMOTE_ADDR'])
      ? ''
      : ' LIMIT 100';

    // 1. Проиндексировать документы, отсутствующие в индексе.
    // 2. Удалить скрытые и удалённые.
    // 3. Всё остальное нужно делать по hookNode().
    $nids = $ctx->db->getResultsV("id", "SELECT `id` FROM `node` "
      ."WHERE `deleted` = 0 AND `published` = 1 "
      ."AND `class` NOT IN ('". join("', '", TypeNode::getInternal()) ."') "
      ."AND `id` NOT IN (SELECT `nid` FROM `node__searchindex`)" . $limi);

    foreach ($nids as $nid) {
      $node = Node::load($nid);
      self::reindexNode($node);
    }
  }

  private static function reindexNode(Node $node)
  {
    $html = '';
    $schema = $node->getSchema();
    $html = $node->text;
   
    $lang = empty($node->lang) ? 'en' : $node->lang;
    $html = "HTTP/1.0 200 OK\n"
      ."Content-Type: text/html; charset=utf-8\n"
      ."Content-Language: {$lang}\n\n"
      ."<html><head><title>{$node->name}</title></head>"
      ."<body><h1>{$node->name}</h1>{$html}</body></html>";

    try {
      if ($url = self::getNodeUrl($node)) {
        mcms::db()->exec('DELETE FROM `node__searchindex` WHERE `nid` = :nid',
          array(':nid' => $node->id));
        mcms::db()->exec('INSERT INTO `node__searchindex` (`nid`, `url`, `html`) '
          .'VALUES (:nid, :url, :html)', array(
            ':nid' => $node->id,
            ':url' => $url,
            ':html' => $html,
          ));
      }
    } catch (TableNotFoundException $e) { }
  }

  private static function getNodeUrl(Node $node)
  {
    $tag = mcms::db()->getResults("SELECT `id`, `code` FROM `node` `n` "
      ."INNER JOIN `node__rel` `r` ON `r`.`tid` = `n`.`id` "
      ."WHERE `r`.`nid` = :nid AND `n`.`class` = 'tag' AND `n`.`deleted` = 0 AND `n`.`published` = 1", array(
        ':nid' => $node->id,
        ));

    $host = Context::last()->url()->host();

    if (!($tag = $tag[0]['id']))
      return null;
    $url = 'http://'. $host .'/'. $tag .'/'. $node->id .'/';

    mcms::flog('indexing ' . $url);

    return $url;
  }
}
