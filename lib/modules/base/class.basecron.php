<?php

class BaseCron
{
  /**
   * @mcms_message ru.molinos.cms.cron
   */
  public static function taskRun(Context $ctx)
  {
    self::fix_linked_objects($ctx);
  }

  private static function fix_linked_objects(Context $ctx)
  {
    // Получаем список объектов, которые обновились после того,
    // как обновились объекты, к которым они привязаны.
    $sql = 'SELECT DISTINCT(`r`.`tid`) AS `id` FROM `node__rel` `r` '
      . 'INNER JOIN `node` `n1` ON `n1`.`id` = `r`.`nid` '
      . 'INNER JOIN `node` `n2` ON `n2`.`id` = `r`.`tid` '
      . 'WHERE `n1`.`updated` > `n2`.`updated` AND `r`.`key` IS NOT NULL';
    $ids = $ctx->db->getResultsV('id', $sql);

    if (!empty($ids)) {
      $ctx->db->beginTransaction();
      foreach ($ids as $id)
        $node = Node::load($id)->touch()->save();
      $ctx->db->commit();
    }
  }
}
