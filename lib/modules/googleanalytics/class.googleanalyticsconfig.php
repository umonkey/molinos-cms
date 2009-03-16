<?php

class GoogleAnalyticsConfig
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module.googleanalytics
   */
  public static function formGetModuleConfig()
  {
    return new Schema(array(
      'account' => array(
        'type' => 'TextLineControl'
        'label' => t('Учётная запись Google Analytics'),
        'description' => t('Получить учётную запись можно на сайте <a href=\'@url\'>Google Analytics</a>, выглядит она примерно так: UA-123456-1.', array(
          '@url' => 'http://www.google.com/analytics/',
          )),
        ),
      'log_uids' => array(
        'type' => 'BoolControl',
        'label' => t('Передавать имена пользователей'),
        'description' => t('При использовании этой опции Google будет получать имена залогиненных пользователей, что позволяет отделять их от анонимных.'),
        ),
      ));
  }
}
