<?php

class SubscriptionConfig
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu()
  {
    return array(
      array(
        're' => 'admin/system/settings/subscription',
        'title' => t('Почтовая рассылка'),
        'method' => 'modman::settings',
        'sort' => 'mailsubscription',
        ),
      );
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.subscription
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'types' => array(
        'type' => 'SetControl',
        'label' => t('Типы рассылаемых документов'),
        'options' => TypeNode::getAccessible(null),
        'store' => true,
        ),
      'sections' => array(
        'type' => 'SectionsControl',
        'label' => t('Рассылать новости из разделов'),
        'group' => t('Разделы'),
        'store' => true,
        ),
      'stylesheet' => array(
        'type' => 'TextLineControl',
        'label' => t('Шаблон'),
        'default' => os::path('lib', 'modules', 'subscription', 'message.xsl'),
        'description' => t('За помощью в написании шаблона обратитесь к документации.'),
        ),
      'subject' => array(
        'type' => 'TextLineControl',
        'label' => t('Заголовок сообщения'),
        'default' => 'Новости сайта %host',
        ),
      ));
  }
}
