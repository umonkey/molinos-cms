<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

require_once(dirname(__FILE__) .'/widget-cart-control.inc');

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

    $options['qty'] = $ctx->get('qty', 1);

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['mode']), $options);
  }

  protected function onGetSimple(array $options)
  {
    $output = null;

    if (!count($cart = $this->getCartContent())) {
      if (!empty($this->welcome_url) and $_SERVER['REQUEST_URI'] != $this->welcome_url)
        $output = t("Корзина пуста, хотите <a href='@link'>ознакомиться с каталогом</a>?", array('@link' => $this->welcome_url));
    }

    else {
      $total = 0;

      $output .= "<div class='cart cart-simple'>";
      $output .= '<h2>'. t('Ваша корзина') .'</h2>';
      $output .= "<table class='chopping-cart'>";

      foreach ($cart as $node) {
        $output .= "<tr>";
        $output .= "<td class='name'>". mcms_plain($node['name']) ."</td>";
        $output .= "<td class='qty'>{$node['qty']}×{$node['price']}</td>";
        $output .= "</tr>";

        $total += $node['sum'];
      }

      $output .= "<tr class='total'><td class='total'><strong>". t('Сумма') ."</strong></td><td class='sum'>". number_format($total, 2) ."</td></tr>";

      $output .= "</table>";
      $output .= "<p class='purge'>". l('Очистить', array($this->getInstanceName() => array('mode' => 'purge'))) ."</p>";
      $output .= "<p class='details'>". l('Заказать', array($this->getInstanceName() => array('mode' => 'details'))) ."</p>";
      $output .= "</div>";
    }

    return $output;
  }

  protected function onGetAdd(array $options)
  {
    $node = Node::load($options['add']);

    parent::checkDocType($node);

    bebop_session_start();

    if (empty($_SESSION['cart'][$node->id]))
      $count = 0;
    else
      $count = $_SESSION['cart'][$node->id];

    $_SESSION['cart'][$node->id] = $count + $options['qty'];
    bebop_session_end();

    $url = bebop_split_url();
    $url['args'][$this->getInstanceName()]['add'] = null;
    bebop_redirect($url);
  }

  protected function onGetPurge(array $options)
  {
    bebop_session_start();

    if (array_key_exists('cart', $_SESSION))
      unset($_SESSION['cart']);

    bebop_session_end();

    $url = bebop_split_url();
    $url['args'][$this->getInstanceName()] = null;
    bebop_redirect($url);
  }

  protected function onGetDetails(array $options)
  {
    bebop_session_start();
    bebop_session_end();

    if (empty($_SESSION['cart']))
      return null;

    $output = array();

    foreach ($_SESSION['cart'] as $k => $v) {
      try {
        $node = Node::load($k);
        $output['list'][] = array(
          'qty' => $v,
          'object' => $node->getRaw(),
          );
      } catch (ObjectNotFoundException $e) {
      }
    }

    return $output;
  }

  protected function onGetConfirm(array $options)
  {
    return parent::formRender('cart-confirm');
  }

  protected function onGetOk(array $options)
  {
    return t('Ваш заказ отправлен, спасибо.');
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
        bebop_session_start();

        foreach ($data['cart'] as $k => $v) {
          if (empty($v['qty']) and array_key_exists($k, $_SESSION['cart']))
            unset($_SESSION['cart'][$k]);
          else
            $_SESSION['cart'][$k] = $v['qty'];
        }

        if (!empty($data['cart_checked']))
          foreach ($_SESSION['cart'] as $k => $v)
            if (in_array($k, $data['cart_checked']))
              unset($_SESSION['cart'][$k]);

        bebop_session_end();
      } else {
        $url = bebop_split_url();
        $url['args'][$this->getInstanceName()] = array('mode' => 'confirm');
        $next = bebop_combine_url($url, false);
      }

      break;

    case 'cart-confirm':
      if (empty($_SESSION['cart']))
        throw new PageNotFoundException();

      $report = $this->getCartReport();

      // Подставляем содержимое заказа в поле.
      $data['node_content_details'] = $report;

      $node = Node::create('order');
      $node->formProcess($data);

      if (!empty($this->email)) {
        $body = t("<p>Пользователь %login только что оформил заказ следующего содержания:</p>",
            array('%login' => mcms::user()->getName()))
            . str_replace('<table>', '<table border=\'1\' cellspacing=\'0\' cellpadding=\'2\'>', $report);

        $body .= t("<p>Пользователь предоставил следующую дополнительную информацию:</p>");
        $body .= $this->getOrderDetails($node);

        bebop_mail(null, $this->email, t('Заказ на сайте %host', array('%host' => $_SERVER['HTTP_HOST'])), $body);
      }

      bebop_session_start();
      unset($_SESSION['cart']);
      bebop_session_end();

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

      $type->setAccess(array(
        'Visitors' => array('c', 'r'),
        'Content Managers' => array('r'),
        'Store Managers' => array('c', 'r', 'u', 'd'),
        ));
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

      $type->setAccess(array(
        'Content Managers' => array('c'),
        ), false);

      // Привязываем новый тип к этому виджету.
      $this->me->linkAddParent($type->id);
    }
  }

  private function getCartContent()
  {
    $result = array();

    bebop_session_start();
    bebop_session_end();

    if (!empty($_SESSION['cart']) and count($nodes = Node::find(array('id' => array_keys($_SESSION['cart']))))) {
      foreach ($nodes as $node) {
        $result[] = array(
          'id' => $node->id,
          'name' => $node->name,
          'qty' => $qty = $_SESSION['cart'][$node->id],
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
