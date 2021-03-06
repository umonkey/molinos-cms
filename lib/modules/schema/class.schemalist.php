<?php

class SchemaList extends AdminListhandler
{
  protected function setUp()
  {
    $this->actions = array('delete', 'publish', 'unpublish', 'reindex', 'touch');
    $this->title = t('Типы документов');
    $this->limit = null;
    $this->hidesearch = true;
    $this->addlink = '?q=admin/create/type&destination='
      . urlencode(MCMS_REQUEST_URI);
  }

  protected function getData()
  {
    $data = Node::find(array(
      'class' => 'type',
      'deleted' => 0,
      '#sort' => '-published name',
      ), Context::last()->db);

    $counts = $this->ctx->db->getResultsKV("name", "count", "SELECT `class` AS `name`, COUNT(*) AS `count` FROM `node` WHERE `deleted` = 0 GROUP BY `class`");

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
