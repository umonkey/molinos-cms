<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CartWidget extends Widget
{
  /**
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'class' => __CLASS__,
      'name' => 'Корзина для покупок',
      'description' => 'Позволяет пользователю добавлять товар в корзину.',
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/CartWidget',
      );
  }

  // Препроцессор параметров.
  protected function getRequestOptions(Context $ctx, array $params)
  {
    $options = parent::getRequestOptions($ctx, $params);
    $options['#cache'] = false;

    // Режим работы задаётся администратором, но переопределяется извне.
    $options['mode'] = $this->get('mode', 'simple');

    // Добавление товара.
    if (null !== ($options['add'] = $this->get('add'))) {
      $options['mode'] = 'add';
      $options['qty'] = $this->get('qty', 1);
    }

    if (empty($options['mode']))
      throw new WidgetHaltedException();

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    if (!$this->checkPermission())
      return '<!-- Нет доступа к корзине (см. права на тип order) -->';

    return $this->dispatch(array($options['mode']), $options);
  }

  protected function onGetSimple(array $options)
  {
    $cart = new Cart($this->ctx);

    $result = $cart->getXML();
    $result .= $cart->getConfigXML();

    return html::wrap('cart', $result, array(
      'mode' => 'simple',
      ));
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

  protected function onGetConfirm(array $options)
  {
    $result = self::onGetSimple($options);

    if (empty($result['content']))
      throw new ForbiddenException(t('Вы не можете оформить заказ, '
        .'т.к. ваша корзина пуста.'));

    $node = Node::create('order');

    $result['mode'] = 'confirm';
    $result['form'] = $node->formGet()->getHTML($node);

    return $result;
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
      'html' => t('Вам необходимо '. html::em('a', array('href' => 'profile?register=1'),'зарегистрироваться'). ' перед оформлением заказа.'),
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
    }

    return $form;
  }

  public function formGetData($id)
  {
    $data = null;

    switch ($id) {
    case 'cart-details':
      $data['cart'] = CartRPC::getCartContent();
      break;
    }

    return $data;
  }

  private function checkPermission()
  {
    return Node::create('order')->checkPermission(ACL::CREATE);
  }
};
