<?php

class MaintenanceConfig
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module.maintenance
   */
  public static function formGetModuleConfig()
  {
    return new Schema(array(
      'state' => array(
        'type' => 'EnumRadioControl',
        'label' => t('Текущее состояние'),
        'options' => array(
          '' => t('Сайт работает'),
          'closed' => t('Ведутся технические работы'),
          ),
        ),
      ));
  }
}
