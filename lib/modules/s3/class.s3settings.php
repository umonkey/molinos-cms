<?php

class S3Settings
{
  /**
   * @mcms_message ru.molinos.cms.module.settings.s3
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'accesskey' => array(
        'type' => 'TextLineControl',
        'label' => t('Ключ доступа'),
        'required' => true,
        ),
      'secretkey' => array(
        'type' => 'TextLineControl',
        'label' => t('Секретный ключ'),
        'required' => true,
        ),
      'bucket' => array(
        'type' => 'TextLineControl',
        'label' => t('Ведро'),
        ),
      ));
  }
}
