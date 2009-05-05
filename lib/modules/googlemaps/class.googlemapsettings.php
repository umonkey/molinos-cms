<?php

class GoogleMapSettings
{
  /**
   * @mcms_message ru.molinos.cms.module.settings.googlemaps
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'key' => array(
        'type' => 'TextLineControl',
        'label' => t('Персональный ключ сайта'),
        'description' => t('Получить ключ можно бесплатно на сайте <a href=\'@url\'>Google Maps</a>.', array(
          '@url' => 'http://code.google.com/apis/maps/signup.html',
          )),
        ),
      ));
  }
}
