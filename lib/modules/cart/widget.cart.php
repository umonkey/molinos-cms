<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CartWidget extends Widget implements iRemoteCall, iModuleConfig
{
  public static function hookRemoteCall(Context $ctx)
  {
      $cart = mcms::session('cart');
      $items = $ctx->post('item');
      $result = self::getCartContent($items);

      $tmp = mcms::modconf('cart');
      if (array_key_exists('email', $tmp))
        $email = $tmp['email'];

      $widgetname = $ctx->post('widgetname');
      $recalc = $ctx->post('recalc');

      if (empty($result) or !empty($recalc)) //Возможно, все товары из корзины уже были удалены
        mcms::redirect($widgetname);

      Context::setGlobal();
      $report = bebop_render_object('widget', $widgetname, null, array('items' => $items, 'mode' => 'report'), 'CartWidget');

      $data = array();

      // Подставляем содержимое заказа в поле.
      $data['details'] = $report;
      $data['uid'] = mcms::user()->id;

      if (empty($data['uid'])) // нужно зарегистрироваться перед оформлением заказа
        mcms::redirect("{$widgetname}?{$widgetname}.mode=notregistered");

      $login = $data['email'] = mcms::user()->email;
      $node = Node::create('order',$data);
      $node->save();

      //Заказ уже отправлен, очистим содержимое корзины
       mcms::session('cart', array());

      if (!empty($email)) {
        $body = t("<p>Пользователь %login только что оформил заказ следующего содержания: %report</p>",
            array('%login' => $login, '%report' => str_replace('<table>', '<table border=\'1\' cellspacing=\'0\' cellpadding=\'2\'>', $report)));

        BebopMimeMail::send(null, $email, 'Новый заказ на сайте', $body);
      }

      $next = "{$widgetname}?{$widgetname}.mode=ok";
      mcms::redirect($next);
  }

  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Корзина для покупок',
      'description' => 'Позволяет пользователю добавлять товар в корзину.',
      );
  }

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

  // Препроцессор параметров.
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $options['#cache'] = false;

    // Режим работы задаётся администратором, но переопределяется извне.
    $options['mode'] = $ctx->get('mode', $this->mode);

    // Добавление товара.
    if (null !== ($options['add'] = $ctx->get('add'))) {
      $options['mode'] = 'add';
      $options['qty'] = $ctx->get('qty', 1);
    }

    if (empty($options['mode']))
      throw new WidgetHaltedException();

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['mode']), $options);
  }

  protected function onGetSimple(array $options)
  {
    $url = new url();
    $widgetname = $url->path;
    $result = array(
      'mode' => 'simple',
      'content' => $this->getCartContent(),
      'sum' => 0,
      'widgetname' => $widgetname
      );

    foreach ($result['content'] as $c)
      $result['sum'] += $c['sum'];

    return $result;
  }

  protected function onGetDetails(array $options)
  {
    $url = new url();
    $widgetname = $url->path;
    $result = array(
      'mode' => 'details',
      'content' => $this->getCartContent(),
      'sum' => 0,
      'widgetname' => $widgetname
      );

    foreach ($result['content'] as $c)
      $result['sum'] += $c['sum'];

    return $result;
  }

  protected function onGetAdd(array $options)
  {
    $node = Node::load($options['add']);

    parent::checkDocType($node);

    if (!is_array($cart = mcms::session('cart')))
      $cart = array();

    if (empty($cart[$node->id]))
      $count = 0;
    else
      $count = $cart[$node->id];

    $cart[$node->id] += $options['qty'];

    mcms::session('cart', $cart);

    $url = new url();
    $url->setarg($this->getInstanceName() .'.add', null);
    $url->setarg($this->getInstanceName() .'.qty', null);

    mcms::redirect(strval($url));
  }

  protected function onGetPurge(array $options)
  {
    if (is_array($cart = mcms::session('cart')))
      mcms::session('cart', null);

    $url = bebop_split_url();
    $url['args'][$this->getInstanceName()] = null;
    mcms::redirect($url);
  }

  protected function onGetConfirm(array $options)
  {
    return array(
      'mode' => 'confirm',
      'form' => parent::formRender('cart-confirm'),
      );
  }

  protected function onGetOk(array $options)
  {
    return array(
      'mode' => 'status',
      'html' => t('Ваш заказ отправлен, спасибо.'),
      );
  }

  protected function onGetNotregistered(array $options)
  {
    return array(
      'mode' => 'status',
      'html' => t('Вам необходимо '. mcms::html('a', array('href' => 'profile?register=1'),'зарегистрироваться'). ' перед оформлением заказа.'),
      );
  }

  protected function onGetHistory(array $options)
  {
    return array(
      'mode' => 'status',
      'message' => '<!-- not implemented -->',
      );
  }

  public function formGet($id)
  {
    $form = null;

    switch ($id) {
    case 'cart-details':
      $form = new Form(array(
        'title' => t('Содержимое корзины'),
        ));
      $form->addControl(new CartControl(array(
        'value' => 'cart',
        )));
      $form->addControl(new ActionsControl(array(
        'value' => 'action',
        'options' => array(
          'refresh' => t('Пересчитать'),
          'confirm' => t('Оформить заказ'),
          ),
        )));
      break;

    case 'cart-confirm':
      $node = Node::create('order');
      $form = $node->formGet(true);
      break;
    }

    return $form;
  }

  public function formGetData($id)
  {
    $data = null;

    switch ($id) {
    case 'cart-details':
      $data['cart'] = $this->getCartContent();
      break;
    }

    return $data;
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
          'uid' => array(
            'type' => 'NumberControl',
            'label' => t('ID пользователя (если зарегистрирован на сайте)'),
            'description' => t('Эта информация нужна нам для обратной связи и доставки (если она осуществляется).'),
            'required' => false,
            ),

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
          'details' => array(
            'type' => 'TextHTMLControl',
            'label' => t('Содержимое заказа'),
            'required' => true,
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

  public function getCartContent($items = array())
  {
    $result = array();

    $cart = mcms::session('cart');

    if (!empty($cart)) {
      $ids = array_keys($cart);

      foreach (Node::find(array('id' => $ids)) as $node) {
        if (empty($items))
          $qty = $cart[$node->id];
        else {
          $del = $items[$node->id]['delete'];
          if ($del) {
            unset($cart[$node->id]);
            continue;
          }
          $qty = $items[$node->id]['qty'];
          $cart[$node->id] = $qty;
        }

        $result[] = array(
          'id' => $node->id,
          'name' => $node->name,
          'qty' => $qty,
          'price' => $node->price,
          'sum' => $node->price * $qty,
          );
      }
    }

    mcms::session('cart', $cart);
    return $result;
  }
};
