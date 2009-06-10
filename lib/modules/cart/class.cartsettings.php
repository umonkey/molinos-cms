<?php

class CartSettings
{
  // Проверяет типы документов, инсталлирует новые.
  // FIXME: перетащить куда-нибудь.
  private function installTypes()
  {
    // ЧАСТЬ ПЕРВАЯ: заказ.
    if (!Node::count(array('class' => 'type', 'name' => 'order'))) {
      $type = Node::create('type', array(
        'name' => 'order',
        'title' => t('Заказ'),
        'description' => t('Документы этого типа создаются автоматически при оформлении заказов на сайте.'),
        'fields' => array(
          /*
          'uid' => array(
            'type' => 'NumberControl',
            'label' => t('ID пользователя (если зарегистрирован на сайте)'),
            'description' => t('Эта информация нужна нам для обратной связи и доставки (если она осуществляется).'),
            'required' => false,
            ),
          */
          'name' => array(
            'type' => 'TextLineControl',
            'label' => t('Ф.И.О.'),
            'description' => t('Эта информация нужна нам для обратной связи и доставки (если она осуществляется).'),
            'required' => true,
            ),
          'email' => array(
            'type' => 'EmailControl',
            'label' => t('Адрес электронной почты'),
            'required' => true,
            ),
          'phone' => array(
            'type' => 'TextLineControl',
            'label' => t('Контактный телефон'),
            ),
          'address' => array(
            'type' => 'TextAreaControl',
            'label' => t('Адрес доставки'),
            ),
          'orderdetauks' => array(
            'type' => 'OrderDetailsControl',
            'label' => t('Содержимое заказа'),
            'required' => false,
            'readonly' => true,
            ),
          ),
        ));
      $type->save();
    }
  }

  private static function getDiscounters()
  {
    $result = array();
    Context::last()->registry->broadcast('ru.molinos.cms.cart.discounter.enum', array(&$result));
    return $result;
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.cart
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'email' => array(
        'type' => 'EmailControl',
        'label' => t('Получатель уведомлений'),
        'description' => t('Почтовый адрес, на который будут приходить уведомления о новых заказах. Все заказы также будут сохранены в виде документов типа "Заказ" (если такого типа на данный момент нет, он будет создан при сохранении этого виджета).'),
        ),
      'discounter' => array(
        'type' => 'EnumControl',
        'label' => t('Класс, обрабатывающий скидки'),
        'options' => self::getDiscounters(),
        'default_label' => t('(нет скидок)'),
        ),
      'discount_threshold' => array(
        'type' => 'NumberControl',
        'label' => t('Минимальная сумма для скидки'),
        'description' => t('Если сумма заказа превышает указанное значение, предоставляется скидка.'),
        ),
      'discount_price' => array(
        'type' => 'NumberControl',
        'label' => t('Размер скидки'),
        'description' => t('Введите сумму в основных единицах, либо размер скидки в процентах от общей стоимости заказа (не включая доставку).'),
        ),
      'delivery_threshold' => array(
        'type' => 'NumberControl',
        'label' => t('Бесплатная доставка от'),
        'description' => t('Если сумма заказа превышает указанное значение, доставка осуществляется бесплатно.'),
        ),
      'delivery_price' => array(
        'type' => 'NumberControl',
        'label' => t('Стоимость доставки'),
        ),
      'invoice_template' => array(
        'type' => 'TextLineControl',
        'label' => t('Шаблон для счетов заказчикам'),
        'description' => t('Относительный путь к XSLT файлу.'),
        'group' => t('Шаблоны'),
        'weight' => 100,
        ),
      'notification_template' => array(
        'type' => 'TextLineControl',
        'label' => t('Шаблон для уведомлений администраторам'),
        'group' => t('Шаблоны'),
        'weight' => 101,
        ),
      ));
  }
}
