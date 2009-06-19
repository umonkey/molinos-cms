<?php

class TinyMCEConfig
{
  /**
   * @mcms_message ru.molinos.cms.module.settings.tinymce
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'theme' => array(
        'type' => 'EnumControl',
        'label' => t('Режим работы'),
        'options' => array(
          'simple' => t('Простой (B/I/U)'),
          'medium' => t('Простой с картинками'),
          'advanced' => t('Всё, что можно'),
          'overkill' => t('На стероидах'),
          ),
        'required' => true,
        'weight' => 20,
        'group' => t('Настройки редактора'),
        ),
      /*
      'gzip' => array(
        'type' => 'BoolControl',
        'label' => t('Использовать компрессию'),
        'group' => t('Настройки редактора'),
        ),
      */
      'initializer' => array(
        'type' => 'TextAreaControl',
        'label' => t('Дополнительные инициализаторы'),
        'description' => t('Например: content_css: mcms_path + "/tiny.css", theme_advanced_styles: "Слева=left,Справа=right"'),
        'group' => t('Настройки редактора'),
        ),
      'routes' => array(
        'type' => 'ListControl',
        'label' => t('Выводить на страницах'),
        'default' => array('admin/edit/', 'admin/create/'),
        'description' => t('Вводить следует только префикс адреса страницы.'),
        ),
      ));
  }
}
