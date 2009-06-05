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

  /**
   * Добавляет специфичные действия для типов документов.
   * @mcms_message ru.molinos.cms.node.actions
   */
  public static function on_get_actions(Context $ctx, Node $node)
  {
    $result = array();

    if ($node->id) {
      switch ($node->class) {
      case 'type':
      default:
        $result['sections'] = array(
          'href' => "admin/structure/taxonomy/setup?node={$node->id}&destination=CURRENT",
          'title' => t('Привязать к разделам'),
          );
        break;
      }
    }

    return $result;
  }

  /**
   * Изменяет список разделов, разрешённых для ноды.
   */
  public static function on_post_setup(Context $ctx)
  {
    if (!($node = $ctx->get('node')))
      throw new BadRequestException();

    $ctx->db->beginTransaction();
    $ctx->db->exec("DELETE FROM `node__rel` WHERE `nid` = ? AND `tid` IN (SELECT `id` FROM `node` WHERE `class` = 'tag')", array($node));

    $params = array($node);
    $sql = "INSERT INTO `node__rel` (`tid`, `nid`) SELECT `id`, ? FROM `node` WHERE `id` " . sql::in($ctx->post('selected'), $params);
    $ctx->db->exec($sql, $params);

    $ctx->db->commit();
    return $ctx->getRedirect();
  }
}
