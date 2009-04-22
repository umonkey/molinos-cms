<?php

class CaptchaSettings
{
  /**
   * @mcms_message ru.molinos.cms.module.settings.captcha
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'types' => array(
        'type' => 'SetControl',
        'label' => t('Проверяемые типы документов'),
        'options' => Node::getSortedList('type', 'title', 'name'),
        ),
      ));
  }
}
