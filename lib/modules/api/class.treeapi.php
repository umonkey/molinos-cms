<?php

class TreeAPI
{
  /**
   * Добавление поля node.xml2 для хранения дерева.
   * @mcms_message ru.molinos.cms.install
   */
  public static function on_install(Context $ctx)
  {
    $t = new TableInfo($ctx->db, 'node');

    if (!$t->columnExists('xmltree')) {
      $t->columnSet('xmltree', array(
        'type' => 'longblob',
        ));
      $t->commit();
    }
  }

  /**
   * Возвращает фрагмент дерева (обработчик api/node/tree.xml).
   * @route GET//api/node/tree.xml
   */
  public static function on_get_tree_xml(Context $ctx)
  {
    $result = null;

    if ($id = $ctx->get('node')) {
      $xml = $ctx->db->getResult("SELECT `xmltree` FROM `node` WHERE `id` = ? AND `deleted` = 0", array($id));
      if (null === $xml)
        $xml = self::on_update_tree($ctx, $id);
    }

    elseif ($type = $ctx->get('type')) {
      if ($row = $ctx->db->getResult("SELECT `id`, `xmltree` FROM `node` WHERE `class` = ? AND `parent_id` IS NULL AND `deleted` = 0", array($type))) {
        if (!empty($row['xmltree']))
          $xml = $row['xmltree'];
        else
          $xml = self::on_update_tree($ctx, $row['id']);
      }
    }

    else
      throw new BadRequestException();


    if (null === $xml)
      throw new PageNotFoundException();

    return new Response($xml, 'text/xml');
  }

  /**
   * Обновление XML нод при установке модуля.
   * @mcms_message ru.molinos.cms.install
   */
  public static function on_install_2(Context $ctx)
  {
    return;
    $ctx->db->beginTransaction();

    $upd = $ctx->db->prepare("UPDATE `node` SET `xmltree` = ? WHERE `id` = ?");
    $step = 0;

    $ids = $ctx->db->getResultsV("id", "SELECT `id` FROM `node` WHERE `parent_id` IS NOT NULL AND `xmltree` IS NULL ORDER BY `left` DESC");
    foreach ($ids as $id) {
      Logger::log("node[{$id}]: updating xmltree", 'xml');
      $upd->execute(array(Node::load($id)->getTreeXML(false), $id));
      if ($step++ == 10) {
        $ctx->db->commit();
        $ctx->db->beginTransaction();
      }
    }

    $ctx->db->commit();
  }

  /**
   * Обновляет XML отдельной ноды.
   */
  private static function on_update_tree(Context $ctx, $id)
  {
    if (null !== ($xml = Node::load($id)->getTreeXML(false))) {
      $ctx->db->beginTransaction();
      $ctx->db->exec("UPDATE `node` SET `xmltree` = ? WHERE `id` = ?", array($xml, $id));
      $ctx->db->commit();
    }
    return $xml;
  }

  /**
   * Обновляет XML при изменении объекта.
   * @mcms_message ru.molinos.cms.hook.node
   */
  public static function on_node_change(Context $ctx, Node $node, $op)
  {
    if ($parents = Node::getNodeParentIds($node->getDB(), $node->id)) {
      $params = array();
      $node->getDB()->exec($sql = "UPDATE `node` SET `xmltree` = NULL WHERE `id` " . sql::in($parents, $params));

      $upd = $node->getDB()->prepare("UPDATE `node` SET `xmltree` = ? WHERE `id` = ?");
      while ($id = array_pop($parents))
        $upd->execute(array(Node::load($id, $node->getDB())->getTreeXML(false), $id));
    }
  }
}
