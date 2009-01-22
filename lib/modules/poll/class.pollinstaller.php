<?php

class PollInstaller implements iInstaller
{
  public static function onInstall(Context $ctx)
  {
    $t = new TableInfo('node__poll');

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

  public static function onUninstall(Context $ctx)
  {
    $t = new TableInfo('node__poll');

    if ($t->exists())
      $t->delete();
  }
}
