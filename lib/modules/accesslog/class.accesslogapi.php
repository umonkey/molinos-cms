<?php

class AccessLogAPI
{
  /**
   * @mcms_message ru.molinos.cms.log.access
   */
  public static function on_log(Context $ctx, Node $node)
  {
    try {
      $ctx->db->beginTransaction();
      $ctx->db->exec("INSERT INTO `node__astat` (`nid`, `timestamp`) VALUES (?, ?)", array($node->id, mcms::now()));
      $ctx->db->commit();
    } catch (TableNotFoundException $e) {
    }
  }
}
