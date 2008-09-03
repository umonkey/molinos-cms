<?php

class SubscriptionConfig implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array(
      'title' => t('Настройка почтовой рассылки'),
      ));

    $form->addControl(new SetControl(array(
      'value' => 'config_types',
      'label' => t('Типы рассылаемых документов'),
      'options' => TypeNode::getAccessible(null),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }
}
