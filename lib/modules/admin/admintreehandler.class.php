<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminTreeHandler
{
  protected $ctx;
  protected $type;
  protected $parent;
  protected $columns;

  public function __construct(RequestContext $ctx)
  {
    $this->ctx = $ctx;
  }

  public function getHTML($preset = null)
  {
    $this->setUp($preset);
    $data = $this->getData();

    $output = '<table class=\'nodelist\'>';
    $output .= $this->getTableHeader();

    foreach ($data as $nid => $node) {
      $row = "<td class='selector'><input type='checkbox' name='nodes[]' value='{$nid}' /></td>";

      foreach ($this->columns as $field) {
        $value = array_key_exists($field, $node) ? $node[$field] : null;

        $row .= "<td class='field-{$field}'>";

        if (empty($value))
          $row .= '&nbsp;';
        else
          $row .= $value;

        $row .= '</td>';
      }

      $parent = empty($node['parent_id']) ? null : $node['parent_id'];

      $row .= '<td class=\'actions\'>';
      $row .= mcms::html('a', array(
        'href' => "/nodeapi.rpc?action=raise&node={$nid}&parent={$parent}&destination=". urlencode($_SERVER['REQUEST_URI'])
        ), 'поднять');
      $row .= mcms::html('a', array(
        'href' => "/nodeapi.rpc?action=sink&node={$nid}&parent={$parent}&destination=". urlencode($_SERVER['REQUEST_URI'])
        ), 'опустить');
      $row .= '</td>';

      $output .= mcms::html('tr', array(
        'class' => empty($node['published']) ? 'unpublished' : 'published',
        ), $row);
    }

    $output .= '</table>';

    return $output;
  }

  protected function getData()
  {
    switch ($this->ctx->get('preset')) {
    case 'taxonomy':
      $data = TagNode::getTags('flat');
      return $data;
    case 'pages':
      $tmp = self::getNodeTree();
      return $tmp;

    default:
      bebop_debug($this->ctx);
    }
  }

  protected function setUp($preset = null)
  {
    switch ($preset) {
    case 'taxonomy':
      $this->type = 'tag';
      $this->parent = null;
      $this->columns = array('name', 'title', 'created');
      break;
    case 'pages':
      $this->type = 'domain';
      $this->parent = null;
      $this->columns = array('name', 'title', 'theme');
      break;
    }
  }

  private function getNodeTree()
  {
    $list = array();
    $user = mcms::user();

    $columns = $this->columns;
    $columns[] = 'parent_id';

    foreach (Node::find(array('class' => $this->type, 'parent_id' => null)) as $root) {
      $children = $root->getChildren('flat');

      foreach ($children as $node) {
        if ($this->type == 'pages' and $node['theme'] == 'admin' and !$user->hasGroup('CMS Developers'))
          continue;

        $item = array(
          'published' => !empty($node['published']),
          'internal' => !empty($node['internal']),
          );

        $link = true;

        foreach ($columns as $field) {
          if ($field == 'actions')
            continue;

          if (empty($node[$field]))
            $text = null;
          else
            $text = mcms_plain($node[$field]);

          if ($field == 'code' and is_numeric($text))
            $text = null;

          if ($link) {
            if (empty($text))
              $text = t('(без названия)');

            $mod = empty($node['description']) ? '' : " class='hint' title='". mcms_plain($node['description']) ."'";

            $text = "<a{$mod} href='/admin/node/{$node['id']}/edit/?destination=". urlencode($_SERVER['REQUEST_URI']) ."'>{$text}</a>";
            $link = false;
          }

          if ($field == 'name')
            $text = str_repeat('&nbsp;', 4 * $node['depth']) . $text;

          $item[$field] = $text;
        }

        if (array_key_exists('actions', $this->columns)) {
          $actions = array();

          $uri = urlencode($_SERVER['REQUEST_URI']);

          $actions[] = "<a href='/admin/node/{$node['id']}/raise/?destination={$uri}'>поднять</a>";
          $actions[] = "<a href='/admin/node/{$node['id']}/sink/?destination={$uri}'>опустить</a>";

          if ($this->tree == 'tag')
            $actions[] = "<a href='/admin/node/create/?BebopNode.class=tag&amp;BebopNode.parent={$node['id']}&amp;destination={$uri}'>добавить</a>";

          $item['actions'] = join('&nbsp;', $actions);
        }

        $list[$node['id']] = $item;
      }
    }

    return $list;
  }

  private function getTableHeader()
  {
    $output = '<tr>';
    $output .= '<th class=\'selector\'>&nbsp;</th>';

    foreach ($this->columns as $col) {
      $output .= mcms::html('th', array(
        'class' => 'field-'. $col,
        ), $col);
    }

    $output .= '<th>Действия</th>';

    return $output .= '</tr>';
  }
};
