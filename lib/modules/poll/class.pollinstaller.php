<?php

class PollInstaller
{
  /**
   * @mcms_message ru.molinos.cms.install
   */
  public static function onInstall(Context $ctx)
  {
    $t = new TableInfo($ctx->db, 'node__poll');

    if (!$t->exists()) {
      $t->columnSet('nid', array(
        'type' => 'int',
        'required' => true,
        'key' => 'mul',
        ));
      $t->columnSet('uid', array(
        'type' => 'int',
        'required' => false,
        'key' => 'mul',
        ));
      $t->columnSet('ip', array(
        'type' => 'varchar(15)',
        'required' => true,
        'key' => 'mul',
        ));
      $t->columnSet('option', array(
        'type' => 'int',
        'required' => true,
        'key' => 'mul',
        ));

      $t->commit();
    }
  }

  /**
   * @mcms_message ru.molinos.cms.uninstall
   */
  public static function onUninstall(Context $ctx)
  {
    $t = new TableInfo($ctx->db, 'node__poll');

    if ($t->exists())
      $t->delete();
  }
}
