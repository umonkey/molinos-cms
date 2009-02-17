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

    $output = html::em('block', array(
      'name' => 'tree',
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
        $r = new Redirect("?q=admin.rpc&action=create"
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
      $schema = Schema::load($this->ctx->db, $this->type);

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

    if (null === ($parent_id = $this->ctx->get('subid')))
      $ids = $this->ctx->db->getResultsV("id", "SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0 AND `parent_id` IS NULL ORDER BY `left`", array($this->type));
    else
      $ids = $this->ctx->db->getResultsV("id", "SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0 AND `parent_id` = ? ORDER BY `left`", array($this->type, $parent_id));

    foreach ($ids as $id)
      $output .= NodeStub::create($id, $this->ctx->db)->getTreeXML('node', 'children');

    return empty($output)
      ? null
      : html::em('data', $output);
  }
};
