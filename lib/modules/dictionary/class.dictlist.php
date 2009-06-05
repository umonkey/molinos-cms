<?php

class DictList extends AdminListHandler
{
  protected function setUp()
  {
    $this->title = t('Справочники');
    $this->types = array('type');
    $this->linkfield = 'title';
    $this->sort = 'name';
    $this->limit = null;
    $this->page = 1;
    $this->actions = array('delete', 'publish', 'unpublish', 'clone', 'touch');
    $this->addlink = 'admin/content/dict/add';
    $this->hidesearch = true;
  }

  protected function getData()
  {
    $data = Node::find(array(
      'class' => 'type',
      'deleted' => 0,
      '#sort' => '-published name',
      ), $this->ctx->db);

    $counts = $this->ctx->db->getResultsKV("name", "count", "SELECT `class` AS `name`, COUNT(*) AS `count` FROM `node` WHERE `deleted` = 0 GROUP BY `class`");

    $nodes = '';
    foreach ($data as $node) {
      if ($node->isdictionary) {
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
