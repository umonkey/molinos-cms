<?php

class jQueryModule
{
  /**
   * @mcms_message ru.molinos.cms.compressor.enum
   */
  public static function on_compressor_enum(Context $ctx)
  {
    $libs = array();

    if ($version = $ctx->modconf('jquery', 'jsversion', '1.2.6'))
      $libs[] = array('script', os::path('lib', 'modules', 'jquery', 'jquery-' . $version . '.min.js'));

    if ($ctx->modconf('jquery', 'ui'))
      $libs[] = array('script', os::path('lib', 'modules', 'jquery', 'jquery-ui-1.5.3.min.js'));

    return $libs;
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.jquery
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'jsversion' => array(
        'type' => 'EnumControl',
        'label' => t('Версия jQuery'),
        'options' => array(
          '1.2.6' => '1.2.6',
          ),
        'required' => true,
        ),
      'ui' => array(
        'type' => 'BoolControl',
        'label' => t('Подключить jQuery UI'),
        ),
      ));
  }
}
