<?php

class AccessLogConfig implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new SetControl(array(
      'value' => 'config_options',
      'label' => t('Отслеживаемые запросы'),
      'options' => array(
        'section' => t('К разделам'),
        'document' => t('К документам'),
        ),
      )));

    return $form;
  }

  public static function hookPostInstall()
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
}
