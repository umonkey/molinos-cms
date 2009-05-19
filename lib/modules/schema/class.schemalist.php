<?php

class SchemaList extends AdminListhandler
{
  protected function setUp()
  {
    $this->actions = array('delete', 'publish', 'unpublish', 'clone', 'reindex', 'touch');
    $this->title = t('Типы документов');
    $this->limit = null;
    $this->hidesearch = true;
    $this->addlink = '?q=admin/create/type&destination='
      . urlencode($_SERVER['REQUEST_URI']);
  }

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
        $tmp = Node::create($node->name);
        $attrs = array(
          'id' => $node->id,
          'name' => $node->name,
          'title' => $node->title,
          'list' => $tmp->getListURL(),
          'published' => (bool)$node->published,
          'dynamic' => $tmp->canEditFields(),
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
