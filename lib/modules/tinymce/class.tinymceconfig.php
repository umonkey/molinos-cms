<?php

class TinyMCEConfig
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module.tinymce
   */
  public static function formGetModuleConfig()
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
        ),
      'gzip' => array(
        'type' => 'BoolControl',
        'label' => t('Использовать компрессию'),
        ),
      'toolbar' => array(
        'type' => 'EnumControl',
        'label' => t('Панель инструментов'),
        'required' => true,
        'options' => array(
          'topleft' => t('Сверху слева'),
          'topcenter' => t('Сверху по центру'),
          'bottomcenter' => t('Снизу по центру'),
          ),
        ),
      'path' => array(
        'type' => 'EnumControl',
        'label' => t('Текущий элемент'),
        'required' => true,
        'options' => array(
          '' => t('Не показывать'),
          'bottom' => t('Снизу'),
          ),
        'description' => t('При отключении пропадает также возможность изменять размер редактора мышью.'),
        ),
      'initializer' => array(
        'type' => 'TextAreaControl',
        'label' => t('Дополнительные инициализаторы'),
        'description' => t('Например: content_css: mcms_path + "/tiny.css", theme_advanced_styles: "Слева=left,Справа=right"'),
        ),
      'pages' => array(
        'type' => 'SetControl',
        'label' => t('Использовать редактор на страницах'),
        'options' => Node::getSortedList('domain'),
        ),
      ));
  }
}
