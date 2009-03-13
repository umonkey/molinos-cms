<?php

class SubscriptionConfig
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module.subscription
   */
  public static function formGetModuleConfig()
  {
    $form = new Form(array(
      'title' => t('Настройка почтовой рассылки'),
      ));

    $form->addControl(new SetControl(array(
      'value' => 'config_types',
      'label' => t('Типы рассылаемых документов'),
      'options' => TypeNode::getAccessible(null),
      'store' => true,
      )));

    $form->addControl(new SectionsControl(array(
      'value' => 'config_sections',
      'label' => t('Рассылать новости из разделов'),
      'group' => t('Разделы'),
      'store' => true,
      )));

    $form->addControl(new TextLineControl(array(
      'value' => 'config_stylesheet',
      'label' => t('Шаблон'),
      'default' => os::path('lib', 'modules', 'subscription', 'message.xsl'),
      'description' => t('За помощью в написании шаблона обратитесь к документации.'),
      )));

    $form->addControl(new TextLineControl(array(
      'value' => 'config_subject',
      'label' => t('Заголовок сообщения'),
      'default' => 'Новости сайта %host',
      )));

    return $form;
  }
}
