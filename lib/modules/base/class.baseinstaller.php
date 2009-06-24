<?php

class BaseInstaller
{
  /**
   * @mcms_message ru.molinos.cms.install
   */
  public static function onInstall(Context $ctx)
  {
    self::updateSortNames($ctx);
    self::updateXML($ctx->db);
  }

  private static function updateSortNames(Context $ctx)
  {
    $ctx->db->beginTransaction();

    $sel = $ctx->db->prepare("SELECT id, name FROM `node` WHERE `name_lc` IS NULL");
    $upd = $ctx->db->prepare("UPDATE `node` SET `name_lc` = ? WHERE `id` = ?");

    for ($sel->execute(); $row = $sel->fetch(PDO::FETCH_ASSOC); )
      $upd->execute(array(Query::getSortName($row['name']), $row['id']));

    $ctx->db->commit();
  }

  private static function updateXML($db)
  {
    $db->beginTransaction();

    $upd = $db->prepare("UPDATE `node` SET `xml` = ? WHERE `id` = ?");
    $sel = $db->exec("SELECT * FROM `node` WHERE `xml` IS NULL");

    while ($row = $sel->fetch(PDO::FETCH_ASSOC)) {
      $id = $row['id'];
      unset($row['id']);
      unset($row['xml']);

      $node = Node::load($id, $db);
      $upd->execute(array($node->getXML(), $id));
    }

    $db->commit();
  }
}
