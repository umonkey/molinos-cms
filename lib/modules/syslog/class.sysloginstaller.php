<?php

class SyslogInstaller
{
  /**
   * @mcms_message ru.molinos.cms.install
   */
  public static function onInstall(Context $ctx)
  {
    $t = new TableInfo($ctx->db, 'node__log');
    $t->columnSet('lid', array(
      'type' => 'int',
      'required' => true,
      'key' => 'pri',
      ));
    $t->columnSet('nid', array(
      'type' => 'int',
      'key' => 'mul',
      ));
    $t->columnSet('uid', array(
      'type' => 'int',
      'key' => 'mul',
      ));
    $t->columnSet('username', array(
      'type' => 'varchar(255)',
      ));
    $t->columnSet('operation', array(
      'type' => 'varchar(255)',
      'key' => 'mul',
      ));
    $t->columnSet('ip', array(
      'type' => 'varchar(15)',
      'key' => 'mul',
      ));
    $t->columnSet('timestamp', array(
      'type' => 'datetime',
      'key' => 'mul',
      ));
    $t->columnSet('message', array(
      'type' => 'varchar(255)',
      ));
    $t->commit();
  }

  /**
   * @mcms_message ru.molinos.cms.uninstall
   */
  public static function onUninstall(Context $ctx)
  {
    $t = new TableInfo($ctx->db, 'node__log');
    if ($t->exists())
      $t->delete();
  }
}
