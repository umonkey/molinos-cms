<?php

class GoogleAnalyticsConfig
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module.googleanalytics
   */
  public static function formGetModuleConfig()
  {
    $form = new Form(array(
      'title' => t('Интеграция с Google Analytics'),
      ));

    $form->addControl(new TextLineControl(array(
      'value' => 'config_account',
      'label' => t('Учётная запись Google Analytics'),
      'description' => t('Получить учётную запись можно на сайте <a href=\'@url\'>Google Analytics</a>, выглядит она примерно так: UA-123456-1.', array(
        '@url' => 'http://www.google.com/analytics/',
        )),
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_log_uids',
      'label' => t('Передавать имена пользователей'),
      'description' => t('При использовании этой опции Google будет получать имена залогиненных пользователей, что позволяет отделять их от анонимных.'),
      )));

    return $form;
  }
}
