<?php

class CaptchaSettings
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module.captcha
   */
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new SetControl(array(
      'value' => 'config_types',
      'label' => t('Проверяемые типы документов'),
      'options' => Node::getSortedList('type', 'title', 'name'),
      )));

    return $form;
  }
}
