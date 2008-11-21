<?php
// Structure Migration Assistant.

class StructureMA
{
  private $widgets = array();
  private $domains = array();
  private $aliases = array();
  private $access = array();

  public function import()
  {
    $this->getWidgets();
    $this->getDomains();
    $this->getAccess();

    return array(
      'widgets' => $this->widgets,
      'aliases' => $this->aliases,
      'domains' => $this->domains,
      'access' => $this->access,
      );
  }

  private function getWidgets()
  {
    $result = array();

    foreach (Node::find(array(
      'class' => 'widget',
      '#sort' => 'name',
      )) as $node)
    {
      $result[$node->name] = array(
        'id' => $node->id,
        'title' => $node->title,
        'class' => $node->classname,
        );

      if (!empty($node->config)) {
        $config = $node->config;

        foreach ($config as $k => $v)
          if (empty($v))
            unset($config[$k]);

        $result[$node->name]['config'] = $config;
      }

      $types = mcms::db()->getResultsV("name", "SELECT v.name FROM node__rev v INNER JOIN node n ON n.rid = v.rid INNER JOIN node__rel r ON r.tid = n.id WHERE r.nid = ? AND n.class = 'type'", array($node->id));
      if (!empty($types))
        $result[$node->name]['config']['types'] = $types;

      $groups = mcms::db()->getResultsV("uid", "SELECT uid FROM node__access WHERE nid = ? AND r = 1", array($node->id));

      if (null === $groups)
        $groups = array(0);

      foreach ($groups as $gid)
        $result[$node->name]['access'][] = intval($gid);
    }

    $this->widgets = $result;
  }

  private function getDomains()
  {
    $nodes = Node::find(array(
      'class' => 'domain',
      'parent_id' => null,
      ));

    foreach ($nodes as $node) {
      if ($node->redirect) {
        $this->aliases[$node->name] = array(
          'target' => $node->redirect,
          );

        foreach (array('defaultsection') as $k)
          if (isset($node->$k))
            $this->aliases[$node->name][$k] = $node->$k;
      }

      else {
        $node->loadChildren();
        $this->addPage($node->name, $node);
      }
    }
  }

  private function addPage($domain, DomainNode $page, $name = '/')
  {
    $data = array(
      'published' => $page->published,
      );

    foreach (array('title', 'http_code', 'theme', 'lang', 'html_charset', 'params', 'defaultsection') as $k) {
      if (isset($page->$k))
        $data[$k] = $page->$k;
    }

    $widgets = Node::find(array(
      'class' => 'widget',
      'tags' => $page->id,
      ));

    foreach ($widgets as $node)
      $data['widgets']['default'][] = $node->name;

    $this->domains[$domain][$name] = $data;

    if (is_array($page->children))
      foreach ($page->children as $child)
        $this->addPage($domain, $child, rtrim($name, '/') . '/' . $child->name);
  }

  private function getAccess()
  {
    $groups = $types = array();

    $data = mcms::db()->getResults("SELECT `a`.`uid`, `v`.`name`, `a`.`c`, `a`.`r`, `a`.`u`, `a`.`d`, `a`.`p` FROM `node__access` `a` INNER JOIN `node` `n` ON `n`.`id` = `a`.`nid` INNER JOIN `node__rev` `v` ON `v`.`rid` = `n`.`rid` WHERE `n`.`deleted` = 0 AND `n`.`class` = 'type' ORDER BY `a`.`uid`");

    foreach ($data as $row) {
      $gid = $row['uid']
        ? 'group:' . $row['uid']
        : 'anonymous';

      foreach (array('c', 'r', 'u', 'd', 'p') as $key) {
        if ($row[$key] and (empty($groups[$gid][$key]) or !in_array($row['name'], $groups[$gid][$key])))
          $groups[$gid][$key][] = $row['name'];
      }
    }

    $data = Node::find(array(
      'class' => 'type',
      'deleted' => 0,
      ));

    foreach ($data as $row)
      if (is_array($row->perm_own))
        $types[$row->name] = $row->perm_own;

    $this->access = array(
      'groups' => $groups,
      'types' => $types,
      );
  }
}
