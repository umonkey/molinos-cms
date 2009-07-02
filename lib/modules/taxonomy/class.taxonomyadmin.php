<?php

class TaxonomyAdmin
{
  public static function on_get_list(Context $ctx)
  {
    $xml = $ctx->db->getResult("SELECT `xmltree` FROM `node` WHERE `class` = 'tag' AND `parent_id` IS NULL");
    if (empty($xml)) {
      $node = Node::load(array(
        'class' => 'tag',
        'deleted' => 0,
        'parent_id' => null,
        ), $ctx->db);
      $xml = $node->getTreeXML(false, true);
    }

    $data = html::em('data', $xml);

    return html::em('content', array(
      'edit' => true,
      'nosearch' => true,
      'name' => 'list',
      'title' => t('Карта разделов сайта'),
      'preset' => 'taxonomy',
      ), $data);
  }

  /**
   * Добавляет специфичные действия для типов документов.
   * @mcms_message ru.molinos.cms.node.actions
   */
  public static function on_get_actions(Context $ctx, Node $node)
  {
    if ($node instanceof TagNode)
      return;
    elseif (!$node->checkPermission(ACL::UPDATE))
      return;

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

  /**
   * Добавляет информацию о разделах в предварительный просмотр.
   * @mcms_message ru.molinos.cms.hook.preview.xml
   */
  public static function on_preview_tags(Node $node)
  {
    if (!$node->checkPermission(ACL::UPDATE))
      return;

    if ($data = $node->getDB()->getResultsKV("id", "name", "SELECT `id`, `name` FROM `node` WHERE `deleted` = 0 AND `class` = 'tag' AND `id` IN (SELECT `tid` FROM `node__rel` WHERE `nid` = ?)", array($node->id))) {
      $result = array();
      foreach ($data as $k => $v)
        $result[] = html::em('a', array(
          'href' => "admin/node/{$k}?destination=CURRENT",
          ), html::plain($v));

      $result = html::em('value', html::cdata(implode(', ', $result)));

      return html::em('field', array(
        'html' => true,
        'title' => t('Находится в разделах'),
        'editurl' => "admin/structure/taxonomy/setup?node={$node->id}&destination=" . urlencode(MCMS_REQUEST_URI),
        ), $result);
    }
  }
}
