<?php

class MaintenanceConfig
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module.maintenance
   */
  public static function formGetModuleConfig()
  {
    $form = new Form(array(
      'title' => t('Управление техническими работами'),
      ));

    $form->addControl(new EnumRadioControl(array(
      'value' => 'config_state',
      'label' => t('Текущее состояние'),
      'options' => array(
        '' => t('Сайт работает'),
        'closed' => t('Ведутся технические работы'),
        ),
      )));

    return $form;
  }
}
