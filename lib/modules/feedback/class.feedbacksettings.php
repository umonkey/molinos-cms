<?php

class FeedbackSettings implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new NodeLinkControl(array(
      'value' => 'config_supervisor',
      'label' => t('Супервизор'),
      'dictionary' => 'contacts',
      'required' => true,
      'description' => t('Выбранный пользователь будет получать все сообщения, независимо от указанного получателя.'),
      'nohide' => true,
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }
}
