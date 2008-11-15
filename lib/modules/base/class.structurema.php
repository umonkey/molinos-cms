<?php
// Structure Migration Assistant.

class StructureMA
{
  private $widgets = array();
  private $domains = array();
  private $aliases = array();

  public function import()
  {
    $this->getWidgets();
    $this->getDomains();

    return array(
      'widgets' => $this->widgets,
      'aliases' => $this->aliases,
      'domains' => $this->domains,
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
        'title' => $node->title,
        'class' => $node->classname,
        );

      if (!empty($node->config))
        $result[$node->name]['config'] = $node->config;

      $groups = mcms::db()->getResultsV("uid", "SELECT uid FROM node__access WHERE nid = ? AND r = 1", array($node->id));

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

  private function addPage($domain, DomainNode $page, $prefix = '/')
  {
    $name = ($page->name == $domain) ? '' : $page->name;

    $data = array(
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

    $this->domains[$domain][$prefix . $name] = $data;

    if (is_array($page->children))
      foreach ($page->children as $child)
        $this->addPage($domain, $child, $prefix . $name);
  }
}
