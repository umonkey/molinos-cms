<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminTreeHandler
{
  protected $ctx;
  protected $type;
  protected $parent;
  protected $columns = array();
  protected $columntitles = array();
  protected $selectors;
  protected $zoomlink;
  protected $addlink;

  public function __construct(Context $ctx)
  {
    $this->ctx = $ctx;
  }

  public function getHTML($preset = null)
  {
    $this->setUp($preset);

    $output = '<h2>'. $this->title .'</h2>';

    if (!empty($_GET['msg']) and ('welcome' == $_GET['msg']))
      $output .= '<p class=\'helpmsg\'>'. t('Вы обратились к ненастроенному домену.  Сейчас вам, скорее всего, следует добавить несколько типовых страниц для вашего нового сайта, затем добавить к нему несколько виджетов.') .'</p>';

    $form = new Form(array(
      'id' => 'nodelist-form',
      'action' => '?q=nodeapi.rpc&action=mass&destination=CURRENT',
      ));
    $form->addControl(new AdminUINodeActionsControl(array(
      'actions' => $this->actions,
      'addlink' => $this->addlink,
      )));
    $form->addControl(new AdminUITreeControl(array(
      'columns' => $this->columns,
      'columntitles' => $this->columntitles,
      'selectors' => $this->selectors,
      'zoomlink' => $this->zoomlink,
      )));

    $form->addControl(new AdminUINodeActionsControl(array(
      'actions' => $this->actions,
      'addlink' => $this->addlink,
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

      if (empty($data)) {
        $r = new Redirect("?q=admin&mode=create"
          ."&parent=". $this->ctx->get('subid')
          ."&type={$this->type}"
          ."&destination=CURRENT");
        $r->send();
      }

      return $data;
    default:
      mcms::debug($this->ctx);
    }
  }

  protected function setUp($preset = null)
  {
    switch ($preset) {
    case 'taxonomy':
      $this->type = 'tag';
      $this->parent = null;
      $this->columns = array('name', 'description', 'link', 'created');
      $this->actions = array('publish', 'unpublish', 'delete', 'clone');
      $this->title = t('Карта разделов сайта');
      $this->zoomlink = "?q=admin/content/list&columns=name,class,uid,created&search=tags%3ANODEID";
      break;
    case 'pages':
      $this->type = 'domain';
      $this->parent = null;
      $this->columns = array('name', 'title', 'language', 'params', 'theme');
      $this->actions = array('publish', 'unpublish', 'delete', 'clone');

      try {
        $node = Node::load($this->ctx->get('subid'));
        $this->title = t('Страницы в домене «%name»',
          array('%name' => $node->name));
      } catch (ObjectNotFoundException $e) {
        $this->title = t('Непонятный домен');
      }

      $this->addlink = '?q=admin/structure/create&type=domain'
        .'&parent='. $this->ctx->get('subid')
        .'&destination=CURRENT';

      break;
    }

    if (!empty($this->type) and !is_array($this->type)) {
      $schema = Node::create($this->type)->schema();

      // Удаляем отсутствующие колонки.
      foreach ($this->columns as $k => $v) {
        if (!isset($schema[$v]))
          unset($this->columns[$k]);
      }

      // Формируем описания колонок.
      foreach ($schema as $k => $v) {
        if (!array_key_exists($k, $this->columntitles))
          $this->columntitles[$k] = $v->label;
      }
    }
  }

  private function getGroup()
  {
    if (array_key_exists('cgroup', $_GET))
      return $_GET['cgroup'];
    elseif (preg_match('@^admin/([a-z]+)/@', $_GET['q'], $m))
      return $m[1];
    return 'content';
  }

  private function getNodeTree()
  {
    $list = array();
    $user = mcms::user();

    $columns = $this->columns;
    $columns[] = 'parent_id';

    $filter = array(
      'class' => $this->type,
      'parent_id' => $this->ctx->get('subid'),
      '#recurse' => 0,
      '#files' => 0,
      '#deleted' => 0,
      );

    foreach (Node::find($filter) as $root) {
      $root->loadChildren($root->class, true);

      $children = $root->getChildren('flat');

      foreach ($children as $node) {
        if ($this->type == 'pages' and $node['theme'] == 'admin' and !$user->hasAccess('u', 'moduleinfo'))
          continue;

        $item = array(
          'id' => $node['id'],
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

          if ($link) {
            $args = array(
              'class' => array(),
              'href' => "?q=admin/". $this->getGroup() ."/edit/{$node['id']}&destination=CURRENT",
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

          $actions[] = mcms::html('a', array('href' => "admin/node/{$node['id']}/raise/?destination=CURRENT"), 'поднять');
          $actions[] = mcms::html('a', array('href' => "admin/node/{$node['id']}/sink/?destination=CURRENT"), 'опустить');

          if ($this->tree == 'tag')
            $actions[] = mcms::html('a', array('href' => "admin/node/create/?BebopNode.class=tag&BebopNode.parent={$node['id']}&destination=CURRENT"), 'добавить');

          $item['actions'] = join('&nbsp;', $actions);
        }

        $list[$node['id']] = $item;
      }
    }

    return $list;
  }
};
