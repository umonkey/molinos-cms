<?php

class CaptchaSettings
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module.captcha
   */
  public static function formGetModuleConfig()
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
