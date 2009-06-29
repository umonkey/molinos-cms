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
    if (!$ctx->user->hasAccess('u', 'tag'))
      throw new ForbiddenException();

    $result = '';

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

  /**
   * Изменение прав на разделы.
   * @route POST//api/taxonomy/access.rpc
   */
  public static function on_post_access(Context $ctx)
  {
    $ctx->user->checkAccess('u', 'tag');

    $ctx->db->beginTransaction();
    $ctx->db->exec("DELETE FROM `node__access` WHERE `nid` IN (SELECT `id` FROM `node` WHERE `class` = 'tag')");
    $sth = $ctx->db->prepare("INSERT INTO `node__access` (`nid`, `uid`, `p`) VALUES (?, ?, 1)");
    foreach ((array)$ctx->post('section') as $nid => $gid) {
      $sth->execute(array($nid, $gid));
      Logger::log("section: {$nid}, owner: {$gid}");
    }
    $ctx->db->commit();

    return $ctx->getRedirect('admin/structure/taxonomy');
    mcms::debug($ctx->post);
  }

  /**
   * Возвращает информацию о разделах, доступных пользователю.
   * @route GET//api/taxonomy/permitted.xml
   */
  public static function on_get_permitted(Context $ctx)
  {
    $filter = array(
      'class' => 'tag',
      'deleted' => 0,
      'id' => self::getPermittedSections($ctx),
      );

    if (!$ctx->user->hasAccess('p', 'tag'))
      $filter['published'] = 1;

    $result = Node::findXML($filter, $ctx->db);

    return new Response(html::em('nodes', $result), 'text/xml');
  }

  /**
   * Возвращает разделы, к которым у пользователя есть доступ.
   */
  public static function getPermittedSections(Context $ctx)
  {
    $params = array();
    $sql = "SELECT `nid` FROM `node__access` WHERE `p` = 1 "
      . "AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = 'tag') "
      . "AND `uid` " . sql::in($ctx->user->getGroups(), $params);
    return (array)$ctx->db->getResultsV("nid", $sql, $params);
  }
}
