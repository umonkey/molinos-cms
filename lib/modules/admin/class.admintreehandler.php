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

    $output = '<h2>'. $this->title .'</h2>';

    $form = new Form(array(
      'id' => 'nodelist-form',
      'action' => '/nodeapi.rpc?action=mass&destination='. urlencode($_SERVER['REQUEST_URI']),
      ));
    $form->addControl(new AdminUINodeActionsControl(array(
      'actions' => $this->actions,
      )));
    $form->addControl(new AdminUITreeControl(array(
      'columns' => $this->columns,
      'selectors' => $this->selectors,
      )));
    $form->addControl(new AdminUINodeActionsControl(array(
      'actions' => $this->actions,
      )));

    $output .= $form->getHTML(array(
      'nodes' => $this->getData(),
      ));

    return $output;
  }

  protected function getData()
  {
    switch ($this->ctx->get('preset')) {
    case 'taxonomy':
    case 'pages':
      $data = self::getNodeTree();

      if (empty($data))
        bebop_redirect("/admin/?mode=create&type={$this->type}&destination=". urlencode($_SERVER['REQUEST_URI']));

      return $data;
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
      $this->columns = array('name', 'link', 'code', 'created');
      $this->actions = array('publish', 'unpublish', 'delete', 'clone');
      $this->title = t('Карта разделов сайта');
      break;
    case 'pages':
      $this->type = 'domain';
      $this->parent = null;
      $this->columns = array('name', 'title', 'language', 'params', 'theme');
      $this->actions = array('publish', 'unpublish', 'delete', 'clone');
      $this->title = t('Типовые страницы');
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
          'class' => $node['class'],
          'parent_id' => $node['parent_id'],
          );

        if (!empty($node['depth']))
          $item['depth'] = $node['depth'];

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
            $args = array(
              'class' => array(),
              'href' => "/admin/?mode=edit&cgroup={$_GET['cgroup']}&id={$node['id']}&destination=". urlencode($_SERVER['REQUEST_URI']),
              'style' => empty($node['depth']) ? null : 'margin-left:'. ($node['depth'] * 10) .'px',
              );

            if (empty($text))
              $text = t('(без названия)');

            if (!empty($node['description'])) {
              $args['title'] = $node['description'];
              $args['class'][] = 'hint';
            }

            $text = mcms::html('a', $args, $text);

            $link = false;
          }

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
};
