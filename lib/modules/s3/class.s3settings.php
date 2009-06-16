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
        'label' => t('Субдомен'),
        'description' => t('Произвольное имя, в домене .s3.amazonaws.com, обычно похоже на что-то вроде «example-com-files».'),
        'default' => str_replace('.', '-', MCMS_HOST_NAME) . '-files',
        ),
      'folder' => array(
        'type' => 'TextLineControl',
        'label' => t('Папка'),
        'description' => t('Файлы можно заливать и в корень, но рекомендуется использовать папку, для порядка.'),
        'default' => 'files',
        ),
      ));
  }
}
