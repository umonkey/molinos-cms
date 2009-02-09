<?php

class SearchInstaller implements iInstaller
{
  public static function onInstall(Context $ctx)
  {
    $t = new TableInfo($ctx->db, 'node__searchindex');
    $t->columnSet('nid', array(
      'type' => 'int',
      'required' => true,
      'key' => 'pri',
      ));
    $t->columnSet('url', array(
      'type' => 'varchar(255)',
      'required' => true,
      ));
    $t->columnSet('html', array(
      'type' => 'mediumblob',
      ));
    $t->commit();
  }

  public static function onUninstall(Context $ctx)
  {
    $t = new TableInfo($ctx->db, 'node__searchindex');
    if ($t->exists())
      $t->delete();
  }
}
