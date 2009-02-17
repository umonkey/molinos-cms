<?php

class CartSettings implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $types = array();

    foreach (TypeNode::getList() as $type) {
      $schema = $type->getSchema();
      if (isset($schema['price']))
        $types[$type->id] = $type->title;
    }

    asort($types);

    $form = new Form(array(
      'title' => t('Настройка корзины'),
      'class' => 'tabbed',
      ));

    $form->addControl(new EmailControl(array(
      'value' => 'config_email',
      'label' => t('Получатель уведомлений'),
      'description' => t('Почтовый адрес, на который будут приходить уведомления о новых заказах. Все заказы также будут сохранены в виде документов типа "Заказ" (если такого типа на данный момент нет, он будет создан при сохранении этого виджета).'),
      )));
    $form->addControl(new EnumControl(array(
      'value' => 'config_discounter',
      'label' => t('Класс, обрабатывающий скидки'),
      'options' => self::getDiscounters(),
      'default_label' => t('(нет скидок)'),
      )));

    $tab = $form->addControl(new FieldSetControl(array(
      'label' => t('Скидка'),
      'tabable' => false,
      )));
    $tab->addControl(new NumberControl(array(
      'value' => 'config_discount_threshold',
      'label' => t('Минимальная сумма для скидки'),
      'description' => t('Если сумма заказа превышает указанное значение, предоставляется скидка.'),
      )));
    $tab->addControl(new NumberControl(array(
      'value' => 'config_discount_price',
      'label' => t('Размер скидки'),
      'description' => t('Введите сумму в основных единицах, либо размер скидки в процентах от общей стоимости заказа (не включая доставку).'),
      )));

    $tab = $form->addControl(new FieldSetControl(array(
      'label' => t('Доставка'),
      'tabable' => false,
      )));
    $tab->addControl(new NumberControl(array(
      'value' => 'config_delivery_threshold',
      'label' => t('Бесплатная доставка от'),
      'description' => t('Если сумма заказа превышает указанное значение, доставка осуществляется бесплатно.'),
      )));
    $tab->addControl(new NumberControl(array(
      'value' => 'config_delivery_price',
      'label' => t('Стоимость доставки'),
      )));

    return $form;
  }

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

    foreach (Loader::getImplementors('iCartDiscounter') as $k)
      $result[$k] = $k;

    return $result;
  }
}
