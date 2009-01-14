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

    $output = html::em('list', array(
      'title' => $this->title,
      'preset' => $preset,
      ), $this->getMassCtl() . $this->getData());

    return $output;
  }

  private function getMassCtl()
  {
    return AdminListHandler::getNodeActions(array(), $this->actions);
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
      $schema = Schema::load($this->type);

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
    $output = '';
    $user = mcms::user();

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

      foreach ($children as $node)
        $output .= html::em('node', $node);
    }

    return empty($output)
      ? null
      : html::em('data', $output);
  }
};
