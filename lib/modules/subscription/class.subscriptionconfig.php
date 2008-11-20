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

    $form->addControl(new SectionsControl(array(
      'value' => 'config_sections',
      'label' => t('Рассылать новости из разделов'),
      'group' => t('Разделы'),
      'store' => true,
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }
}
