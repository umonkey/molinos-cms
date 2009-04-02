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
  protected $preset;
  protected $subid;

  public function __construct(Context $ctx, $subid)
  {
    $this->ctx = $ctx;
    $this->subid = $subid;
  }

  public function getHTML($preset = null)
  {
    $this->setUp($this->preset = $preset);

    $output = html::em('content', array(
      'name' => 'tree',
      'title' => $this->title,
      'preset' => $preset,
      'addlink' => $this->addlink,
      ), $this->getMassCtl() . $this->getData());

    return $output;
  }

  private function getMassCtl()
  {
    return AdminListHandler::getNodeActions(array(), $this->actions);
  }

  protected function getData()
  {
    switch ($this->preset) {
    case 'taxonomy':
    case 'pages':
      $data = self::getNodeTree();

      if (empty($data)) {
        $url = 'admin/create/' . $this->type;
        if ($parent_id = $this->getParentId())
          $url .= '/' . $parent_id;
        $url .= '?destination=CURRENT';
        $r = new Redirect($url);
        $r->send();
      }

      return $data;
    default:
      mcms::debug($this->ctx->get('preset'), $this->ctx);
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
        $node = Node::load(array(
          'class' => 'domain',
          'deleted' => 0,
          'name' => $this->subid,
          ));
        $this->title = t('Страницы в домене «%name»',
          array('%name' => $node->name));
      } catch (ObjectNotFoundException $e) {
        $this->title = t('Непонятный домен');
      }

      $this->addlink = 'admin/create/domain'
        . '/'. $this->getParentId()
        . '?destination=' . urlencode(MCMS_REQUEST_URI);

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

    if (null === ($parent_id = $this->getParentId()))
      $ids = $this->ctx->db->getResultsV("id", "SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0 AND `parent_id` IS NULL ORDER BY `left`", array($this->type));
    else
      $ids = $this->ctx->db->getResultsV("id", "SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0 AND `parent_id` = ? ORDER BY `left`", array($this->type, $parent_id));

    foreach ($ids as $id)
      $output .= NodeStub::create($id, $this->ctx->db)->getTreeXML('node', 'children');

    return empty($output)
      ? null
      : html::em('data', $output);
  }

  private function getParentId()
  {
    switch ($this->preset) {
    case 'taxonomy':
      return $this->subid;
    case 'pages':
      return Node::load(array(
        'class' => 'domain',
        'deleted' => 0,
        'name' => $this->subid,
        ))->id;
    }
  }
};
