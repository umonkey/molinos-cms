<?php

class RatingInstaller
{
  /**
   * @mcms_message ru.molinos.cms.install
   */
  public static function onInstall(Context $ctx)
  {
    $t = new TableInfo($ctx->db, 'node__rating');
    $t->columnSet('nid', array(
      'type' => 'int',
      'required' => true,
      'key' => 'mul',
      ));
    $t->columnSet('uid', array(
      'type' => 'int',
      'key' => 'mul',
      ));
    $t->columnSet('ip', array(
      'type' => 'varchar(15)',
      'key' => 'mul',
      ));
    $t->columnSet('rate', array(
      'type' => 'decimal(5,0)',
      'required' => true,
      'key' => 'mul',
      ));
    $t->columnSet('sid', array(
      'type' => 'varchar(255)',
      'key' => 'mul',
      ));
    $t->commit();
  }

  /**
   * @mcms_message ru.molinos.cms.uninstall
   */
  public static function onUninstall(Context $ctx)
  {
    $t = new TableInfo($ctx->db, 'node__rating');
    if ($t->exists())
      $t->delete();
  }
}
