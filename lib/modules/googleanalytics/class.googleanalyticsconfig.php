<?php

class GoogleAnalyticsConfig
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu()
  {
    return array(
      array(
        're' => 'admin/system/settings/googleanalytics',
        'title' => t('Google Analytics'),
        'method' => 'modman::settings',
        ),
      );
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.googleanalytics
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'account' => array(
        'type' => 'TextLineControl',
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
