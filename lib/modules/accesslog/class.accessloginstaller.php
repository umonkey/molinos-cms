<?php

class AccessLogInstaller implements iInstaller
{
  public static function onInstall(Context $ctx)
  {
    $t = new TableInfo('node__astat');

    if (!$t->exists()) {
      $t->columnSet('id', array(
        'type' => 'int',
        'required' => true,
        'key' => 'pri',
        'autoincrement' => true,
        ));
      $t->columnSet('timestamp', array(
        'type' => 'datetime',
        ));
      $t->columnSet('nid', array(
        'type' => 'int',
        'required' => false,
        'key' => 'mul',
        ));
      $t->columnSet('ip', array(
        'type' => 'varchar(15)',
        'required' => true,
        'key' => 'mul',
        ));
      $t->columnSet('referer', array(
        'type' => 'varchar(255)',
        'key' => 'mul',
        ));

      $t->commit();
    }
  }

  public static function onUninstall(Context $ctx)
  {
    $t = new TableInfo('node__astat');

    if ($t->exists())
      $t->delete();
  }
}
