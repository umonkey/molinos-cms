<?php

class AccessLogAPI
{
  /**
   * Создание таблицы для хранения данных.
   * @mcms_message ru.molinos.cms.install
   */
  public static function on_install(Context $ctx)
  {
    TableInfo::check('node__astat', array(
      'lid' => array(
        'type' => 'integer',
        'key' => 'pri',
        'autoincrement' => true,
        ),
      'nid' => array(
        'type' => 'integer',
        'required' => true,
        ),
      'timestamp' => array(
        'type' => 'datetime',
        ),
      ));
  }

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
