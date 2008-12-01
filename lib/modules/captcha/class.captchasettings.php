<?php

class CaptchaSettings implements iModuleConfig
{
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

  public static function hookPostInstall()
  {
  }
}
