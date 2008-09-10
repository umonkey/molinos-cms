<?php

class SearchIndexer implements iNodeHook
{
  public static function hookNodeUpdate(Node $node, $op)
  {
    switch ($op) {
    case 'create':
    case 'update':
    case 'publish':
    case 'restore':
      self::reindexNode($node);
      break;
    case 'delete':
    case 'erase':
    case 'unpublish':
      mcms::db()->exec("DELETE FROM `node__searchindex` WHERE `nid` = :nid",
        array(':nid' => $node->id));
      break;
    }
  }

  private static function reindexNode($node)
  {
    static $schema = null;

    if (null === $schema)
      $schema = TypeNode::getSchema();

    if (!is_object($node))
      $node = Node::load(array('id' => $node));

    if (in_array($node->class, TypeNode::getInternal()))
      return;

    if (array_key_exists($node->class, $schema)) {
      foreach ($schema[$node->class]['fields'] as $k => $v) {
        if (isset($node->$k)) {
          $html .= '<strong>'. mcms_plain($v['label']) .'</strong>';
          $html .= '<div class=\'data\'>'. $node->$k .'</div>';
        }
      }
    }

    $lang = empty($node->lang) ? 'en' : $node->lang;
    $html = "HTTP/1.0 200 OK\n"
      ."Content-Type: text/html; charset=utf-8\n"
      ."Content-Language: {$lang}\n\n"
      ."<html><head><title>{$node->name}</title></head>"
      ."<body><h1>{$node->name}</h1>{$html}</body></html>";

    try {
      mcms::db()->exec('DELETE FROM `node__searchindex` WHERE `nid` = :nid',
        array(':nid' => $node->id));
      mcms::db()->exec('INSERT INTO `node__searchindex` (`nid`, `url`, `html`) '
        .'VALUES (:nid, :url, :html)', array(
          ':nid' => $node->id,
          ':url' => self::getNodeUrl($node),
          ':html' => $html,
        ));
    } catch (TableNotFoundException $e) { }
  }
}
