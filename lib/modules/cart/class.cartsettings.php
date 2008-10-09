<?php

class CartSettings implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $types = array();

    foreach (TypeNode::getSchema() as $type => $schema)
      if (!empty($schema['fields']['price']))
        $types[$schema['id']] = $schema['title'];

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


    return $form;
  }

  public static function hookPostInstall()
  {
  }

  public function formHookConfigSaved()
  {
    $this->installTypes();
  }

  // Проверяет типы документов, инсталлирует новые.
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

    $products = 0;
    $productid = null;

    // ЧАСТЬ ВТОРАЯ: товар.
    foreach (TypeNode::getSchema() as $type => $typeinfo) {
      if (!empty($typeinfo['fields']['price']))
        $products++;
      if ($type == 'product')
        $productid = $typeinfo['id'];
    }

    if (0 == $products) {
      // Тип с именем product уже есть -- добавляем цену.
      if (null !== $productid) {
        $type = Node::load($productid);
        $type->fieldSet('price', array(
          'label' => t('Цена'),
          'type' => 'FloatControl',
          'required' => true,
          'suffix' => t('р'),
          ));
      }

      // Типа с именем product нет, создаём.
      else {
        $type = Node::create('type', array(
          'name' => 'product',
          'title' => t('Товар'),
          'description' => t('Основной элемент наполнения каталога интернет-магазина. В этот тип можно добавить новые поля, при необходимости его можно клонировать.'),
          'fields' => array(
            'name' => array(
              'label' => t('Название'),
              'type' => 'TextLineControl',
              'required' => true,
              'internal' => true,
              ),
            'description' => array(
              'label' => t('Описание товара'),
              'type' => 'TextAreaControl',
              'description' => t('Здесь обычно пишут подробное описание товара.'),
              'required' => true,
              ),
            'price' => array(
              'label' => t('Цена'),
              'type' => 'FloatControl',
              'required' => true,
              'suffix' => t('р'),
              ),
            'picture' => array(
              'label' => t('Изображение товара'),
              'type' => 'AttachmentControl',
              'required' => false,
              ),
            ),
          ));
      }

      $type->save();

      // Привязываем новый тип к этому виджету.
      $this->me->linkAddParent($type->id);
    }
  }
}
