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
}
