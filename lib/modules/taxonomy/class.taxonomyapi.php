<?php

class TaxonomyAPI
{
  /**
   * Возвращает разделы, в которые можно помещать документы запрошенного типа.
   * Для типов документов всегда возвращает все разделы.
   */
  public static function on_get_enabled(Context $ctx)
  {
    $node = Node::load($ctx->get('node'), $ctx->db);

    $filter = array(
      'class' => 'tag',
      'deleted' => 0,
      );

    $options = array(
      'multiple' => true,
      );

    if ('type' != $node->class) {
      $type = Node::load(array(
        'class' => 'type',
        'name' => $node->class,
        'deleted' => 0,
        ), $ctx->db);
      $filter['tagged'] = $type->id;
      if (!in_array($node->class, $ctx->config->getArray('modules/taxonomy/multitagtypes')))
        unset($options['multiple']);
      $options['typeid'] = $type->id;
    }

    $filter['id'] = self::getPermittedSections($ctx);

    return new Response(html::em('nodes', $options, Node::findXML($filter, $ctx->db)), 'text/xml');
  }

  /**
   * Возвращает разделы, в которые помещён документ.
   */
  public static function on_get_selected(Context $ctx)
  {
    $xml = Node::findXML(array(
      'deleted' => 0,
      'class' => 'tag',
      'tagged' => $ctx->get('node'),
      ));
    return new Response(html::em('nodes', $xml), 'text/xml');
  }

  /**
   * Добавляет в XML нод информацию о разделах.
   * @mcms_message ru.molinos.cms.node.xml
   */
  public static function on_node_xml(Node $node)
  {
    list($sql, $params) = Query::build(array(
      'class' => 'tag',
      'deleted' => 0,
      'tagged' => $node->id,
      ))->getSelect(array('id', 'published', 'name', 'class'));

    $data = $node->getDB()->getResults($sql, $params);

    $result = '';
    foreach ($data as $row)
      $result .= html::em('node', $row);

    return html::wrap('taxonomy', $result);
  }

  /**
   * Возвращает информацию о правах на разделы.
   * @route GET//api/taxonomy/access.xml
   */
  public static function on_get_access(Context $ctx)
  {
    if (!$ctx->user->hasAccess(ACL::UPDATE, 'tag'))
      throw new ForbiddenException();

    $data = $ctx->db->getResults("SELECT n.id, n.parent_id, n.name, (SELECT MIN(uid) FROM {node__access} WHERE nid = n.id AND p = 1) AS `publishers`, (SELECT MIN(uid) FROM {node__access} WHERE nid = n.id AND u = 1) AS `owners` FROM {node} n WHERE n.class = 'tag' AND n.deleted = 0 ORDER BY n.left");

    $result = self::recurse($data, null);

    return new Response(html::em('sections', $result), 'text/xml');

    $perms = $ctx->db->getResultsKV("nid", "gid", "SELECT a.nid AS nid, MIN(a.uid) AS gid FROM node__access a INNER JOIN node n ON n.id = a.nid INNER JOIN node g ON g.id = a.uid WHERE n.class = 'tag' AND g.class = 'group' AND a.p = 1 GROUP BY a.nid");
    $data = Node::getSortedList('tag');

    $tmp = '';
    foreach ($data as $k => $v) {
      $gid = array_key_exists($k, $perms)
        ? $perms[$k]
        : null;
      $tmp .= html::em('section', array(
        'id' => $k,
        'group' => $gid,
        'level' => 1 + ((strlen($v) - strlen(ltrim($v))) / 2),
        ), html::cdata(trim($v)));
    }
    $result .= html::wrap('sections', $tmp);

    return new Response($result, 'text/xml');
  }

  private static function recurse(array $data, $parent_id)
  {
    $result = '';

    foreach ($data as $row) {
      if ((null === $parent_id and empty($row['parent_id'])) or $parent_id == $row['parent_id']) {
        $children = self::recurse($data, $row['id']);
        $result .= html::em('section', $row, $children);
      }
    }

    return $result;
  }

  /**
   * Изменение прав на разделы.
   * @route POST//api/taxonomy/access.rpc
   */
  public static function on_post_access(Context $ctx)
  {
    $ctx->user->checkAccess(ACL::UPDATE, 'tag');

    if ($sections = (array)$ctx->post('sections')) {
      $publishers = $ctx->post('publishers');
      $owners = $ctx->post('owners');

      $ctx->db->beginTransaction();
      ACL::resetNode($sections);
      foreach ($sections as $nid) {
        if ($publishers == $owners) {
          ACL::set($nid, $owners, ACL::CREATE|ACL::READ|ACL::UPDATE|ACL::DELETE|ACL::PUBLISH);
        } else {
          ACL::set($nid, $publishers, ACL::PUBLISH);
          ACL::set($nid, $owners, ACL::CREATE|ACL::READ|ACL::UPDATE|ACL::DELETE);
        }
      }
      $ctx->db->commit();
    }

    return $ctx->getRedirect('admin/access/taxonomy');
  }

  /**
   * Возвращает информацию о разделах, доступных пользователю.
   * @route GET//api/taxonomy/permitted.xml
   */
  public static function on_get_permitted(Context $ctx)
  {
    $result = '';

    $pub = ACL::getPermittedNodeIds(ACL::PUBLISH, 'tag');
    $upd = ACL::getPermittedNodeIds(ACL::UPDATE, 'tag');

    $result = '';
    foreach (array_unique(array_merge($pub, $upd)) as $id)
      $result .= html::em('section', array(
        'id' => $id,
        'publish' => in_array($id, $pub),
        'edit' => in_array($id, $upd),
        ));

    return new Response(html::em('sections', $result), 'text/xml');
  }

  /**
   * Возвращает разделы, к которым у пользователя есть доступ.
   */
  public static function getPermittedSections(Context $ctx)
  {
    return ACL::getPermittedNodeIds(ACL::PUBLISH, 'tag');
  }
}
