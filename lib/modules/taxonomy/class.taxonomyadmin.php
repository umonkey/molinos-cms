<?php

class TaxonomyAdmin extends AdminTreeHandler
{
  public static function on_get_list(Context $ctx)
  {
    $tmp = new TaxonomyAdmin($ctx);
    return $tmp->getHTML('taxonomy', array(
      'edit' => $ctx->user->hasAccess('u', 'tag'),
      'nosearch' => true,
      ));
  }

  protected function setUp()
  {
    $this->type = 'tag';
    $this->parent = null;
    $this->columns = array('name', 'description', 'link', 'created');
    $this->actions = array('publish', 'unpublish', 'delete', 'clone');
    $this->title = t('Карта разделов сайта');
    $this->zoomlink = "?q=admin/content/list&columns=name,class,uid,created&search=tags%3ANODEID";
  }

  protected function getData()
  {
    $data = self::getNodeTree();
    $data .= self::getCounts();

    if (empty($data)) {
      $url = 'admin/create/tag';
      if ($parent_id = $this->getParentId())
        $url .= '/' . $parent_id;
      $url .= '?destination=CURRENT';
      $r = new Redirect($url);
      $r->send();
    }

    return $data;
  }

  protected static function getCounts()
  {
    return; // TODO: доработать шаблон

    $data = Context::last()->db->getResultsKV("id", "count", "SELECT r.tid AS id, COUNT(*) AS count FROM node__rel r INNER JOIN node n1 ON n1.id = r.tid INNER JOIN node n2 on n2.id = r.nid WHERE n1.class = 'tag' AND n2.class <> 'tag' AND n1.deleted = 0 AND n2.deleted = 0 GROUP BY r.tid");

    $result = '';
    foreach ($data as $k => $v)
      $result .= html::em('count', array(
        'id' => $k,
        'cnt' => $v,
        ));

    return html::wrap('counts', $result);
  }
}
