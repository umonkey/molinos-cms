<?php

class SchemaList extends AdminListhandler
{
  protected function getData()
  {
    $data = Node::find($this->ctx->db, array(
      'class' => 'type',
      'deleted' => 0,
      '#sort' => '-published name',
      ));

    $counts = $this->ctx->db->getResultsKV("name", "count", "SELECT `class` AS `name`, COUNT(*) AS `count` FROM `node` GROUP BY `class`");

    $nodes = '';
    foreach ($data as $node) {
      if (!$node->isdictionary) {
        $attrs = array(
          'id' => $node->id,
          'name' => $node->name,
          'title' => $node->title,
          'list' => Node::create($node->name)->getListURL(),
          'published' => (bool)$node->published,
          );
        $attrs['count'] = array_key_exists($node->name, $counts)
          ? $counts[$node->name]
          : 0;
        $nodes .= html::em('node', $attrs);
      }
    }

    return html::wrap('data', $nodes);
  }
}
