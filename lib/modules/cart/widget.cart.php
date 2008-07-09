<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CartWidget extends Widget
{
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

  public static function formGetConfig()
  {
    $types = array();

    foreach (TypeNode::getSchema() as $type => $schema)
      if (!empty($schema['fields']['price']))
        $types[$schema['id']] = $schema['title'];

    asort($types);

    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_mode',
      'label' => t('Режим работы'),
      'description' => t('Здесь вы определяете, в каком виде пользователю будет показан именно этот виджет. На странице могут находиться другие виджеты, работающие в другом режиме, но содержимое корзины для них всегда одинаково.'),
      'options' => array(
        'simple' => t('Простой список'),
        'details' => t('Управление заказом'),
        'history' => t('История заказов'),
        ),
      )));
    $form->addControl(new EmailControl(array(
      'value' => 'config_email',
      'label' => t('Получатель уведомлений'),
      'description' => t('Почтовый адрес, на который будут приходить уведомления о новых заказах. Все заказы также будут сохранены в виде документов типа "Заказ" (если такого типа на данный момент нет, он будет создан при сохранении этого виджета).'),
      )));
    $form->addControl(new SetControl(array(
      'value' => 'config_types',
      'label' => t('Используемые типы документов'),
      'description' => t("С документами других типов корзина работать не будет. В этот список попадают все типы документов, у которых есть поле price."),
      'options' => $types,
      )));
    $form->addControl(new TextLineControl(array(
      'value' => 'config_welcome_url',
      'label' => t('Ссылка на каталог'),
      'description' => t("Если корзина пуста, пользователь увидит соответствущее сообщение с предложением пройти по указанной ссылке для выбора товара. Если ссылку не указывать, виджет вообще не будет отображаться, пока корзина пуста (о способах добавления товара в корзину можно прочитать в <a href='@link'>документации</a>).", array('@link' => 'http://code.google.com/p/molinos-cms/wiki/CartWidget')),
      'default' => t('(не используется)'),
      )));

    return $form;
  }

  public function formHookConfigSaved()
  {
    $this->installTypes();
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);
    $options['#nocache'] = true;

    // Режим работы задаётся администратором, но переопределяется извне.
    $options['mode'] = $ctx->get('mode', $this->mode);

    // Добавление товара.
    if (null !== ($options['add'] = $ctx->get('add')))
      $options['mode'] = 'add';

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
    $result = array(
      'mode' => 'simple',
      'content' => $this->getCartContent(),
      );

    $url = new url();

    $url->setarg($this->GetInstanceName() .'.mode', 'purge');
    $result['links']['purge'] = strval($url);

    $url->setarg($this->GetInstanceName() .'.mode', 'details');
    $result['links']['details'] = strval($url);

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

    $cart[$node->id] = ++$count;

    mcms::session('cart', $cart);

    $url = bebop_split_url();
    $url['args'][$this->getInstanceName()]['add'] = null;

    mcms::redirect($url);
  }

  protected function onGetPurge(array $options)
  {
    if (is_array($cart = mcms::session('cart')))
      mcms::session('cart', null);

    $url = bebop_split_url();
    $url['args'][$this->getInstanceName()] = null;
    mcms::redirect($url);
  }

  protected function onGetDetails(array $options)
  {
    $cart = mcms::session('cart');

    if (empty($cart))
      return null;

    return array(
      'mode' => 'details',
      'form' => parent::formRender('cart-details'),
      );
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
      'message' => t('Ваш заказ отправлен, спасибо.'),
      );
  }

  protected function onGetHistory(array $options)
  {
    return array(
      'mode' => 'status',
      'message' => '<!-- not implemented -->',
      );
  }

  protected function onGetHistory(array $options)
  {
    return '<!-- not implemented -->';
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

  public function formProcess($id, array $data)
  {
    $next = null;

    switch ($id) {
    case 'cart-details':
      if ($data['action'] == 'refresh') {
        if (!is_array($cart = mcms::session('cart')))
          $cart = array();

        foreach ($data['cart'] as $k => $v) {

          if (empty($v['qty'])  and array_key_exists($k, $cart))
            unset($cart[$k]);
          else
            $cart[$k] = $v['qty'];
        }

        if (!empty($data['cart_checked']))
          foreach ($cart as $k => $v)
            if (in_array($k, $data['cart_checked']))
              unset($cart[$k]);

        mcms::session('cart', $cart);

        $url = new url();
        $url->setarg($this->GetInstanceName() .'.mode', 'details');
        $next = strval($url);
      } else {
        $url = bebop_split_url();
        $url['args'][$this->getInstanceName()] = array('mode' => 'confirm');
        $next = bebop_combine_url($url, false);
      }
      break;

    case 'cart-confirm':
      $cart = mcms::session('cart');

      if (empty($cart))
        throw new PageNotFoundException();

      $report = $this->getCartReport();

      // Подставляем содержимое заказа в поле.
      $data['node_content_details'] = $report;

      $node = Node::create('order');
      $node->formProcess($data);

      if (!empty($this->email)) {
        $body = t("<p>Пользователь %login только что оформил заказ следующего содержания:</p>",
            array('%login' => mcms::user()->login))
            . str_replace('<table>', '<table border=\'1\' cellspacing=\'0\' cellpadding=\'2\'>', $report);

        $body .= t("<p>Пользователь предоставил следующую дополнительную информацию:</p>");
        $body .= $this->getOrderDetails($node);

        bebop_mail(null, $this->email, t('Заказ на сайте %host', array('%host' => $_SERVER['HTTP_HOST'])), $body);
      }

      $url = bebop_split_url();
      $url['args'][$this->getInstanceName()] = array('mode' => 'ok');
      $next = bebop_combine_url($url, false);

      break;
    }

    return $next;
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

  private function getCartContent()
  {
    $result = array();

    $cart = mcms::session('cart');

    if (!empty($cart)) {
      $ids = array_keys($cart);

      foreach (Node::find(array('id' => $ids)) as $node) {
        $result[] = array(
          'id' => $node->id,
          'name' => $node->name,
          'qty' => $qty = $s->cart[$node->id],
          'price' => $node->price,
          'sum' => $node->price * $qty,
          );
      }
    }

    return $result;
  }

  private function getCartReport()
  {
    $total = 0;

    $report = "<table>";
    $report .= "<tr><th>Код</th><th>Название</th><th>Количество</th><th>Цена</th><th>Сумма</th></tr>";

    foreach ($this->getCartContent() as $row) {
      $report .= "<tr>";
      $report .= "<td>{$row['id']}</td>";
      $report .= "<td>". mcms_plain($row['name']) ."</td>";
      $report .= "<td>{$row['qty']}</td>";
      $report .= "<td style='text-align: right'>{$row['price']}</td>";
      $report .= "<td style='text-align: right'>{$row['sum']}</td>";
      $report .= "</tr>";

      $total += $row['sum'];
    }

    $report .= "<tr><td>&nbsp;</td><td colspan='3'><strong>Итого:</strong></td><td style='text-align: right'><strong>". number_format($total, 2) ."</strong></td></tr>";

    $report .= "</table>";

    return $report;
  }

  private function getOrderDetails(Node $node)
  {
    $schema = TypeNode::getSchema($node->class);

    $output = '<table border=\'0\' cellspacing=\'2\' cellpadding=\'0\'>';

    foreach ($schema['fields'] as $k => $v) {
      if (!empty($node->$k) and $k != 'details') {
        $output .= "<tr><th>". mcms_plain($v['label']) .":</th><td>". mcms_plain($node->$k) ."</td></tr>";
      }
    }

    $output .= '</table>';

    return $output;
  }
};
