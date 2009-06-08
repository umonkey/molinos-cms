<?php

class Cascader
{
  /**
   * Автоматическое обновление XML нод при их сохранении.
   * @mcms_message ru.molinos.cms.hook.node
   */
  public static function on_node_save(Context $ctx, Node $node)
  {
    self::update_node_xml($node);
  }

  /**
   * Ручное обновление XML через меню с действиями.
   * @mcms_message ru.molinos.cms.node.actions
   */
  public static function on_get_actions(Context $ctx, Node $node)
  {
    return array(
      'refresh' => array(
        'href' => 'nodeapi/refresh?node=' . $node->id
          . '&destination=CURRENT',
        'title' => 'Обновить XML',
        'icon' => 'dump',
        ),
      );
  }

  /**
   * Обновление XML ноды и всех её связей.
   */
  private static function update_node_xml(Node $node)
  {
    if ($ids = self::get_ids($node)) {
      $node->getDB()->beginTransaction();
      $upd = $node->getDB()->prepare("UPDATE `node` SET `xml` = ? WHERE `id` = ?");
      foreach ($ids as $id) {
        $upd->execute(array(Node::load($id, $node->getDB())->getXML(), $id));
        mcms::flog("node[{$id}]: XML updated");
      }
      $node->getDB()->commit();
    }
  }

  private static function get_ids(Node $node)
  {
    $ids = array($node->id);

    while (true) {
      $params = array();
      $sql = "SELECT DISTINCT `id` FROM `node` WHERE `deleted` = 0 AND `id` IN "
        . "(SELECT `tid` FROM `node__rel` WHERE `nid` " . sql::in($ids, $params) . " AND `key` IS NOT NULL)"
        . " AND `id` " . sql::notIn($ids, $params);
      $rows = $node->getDB()->getResultsV("id", $sql, $params);
      if (empty($rows))
        break;
      foreach ($rows as $id)
        $ids[] = $id;
    }

    return $ids;
  }
}
