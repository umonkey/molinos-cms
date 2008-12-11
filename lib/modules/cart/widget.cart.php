<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CartWidget extends Widget
{
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Корзина для покупок',
      'description' => 'Позволяет пользователю добавлять товар в корзину.',
      );
  }

  // Препроцессор параметров.
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $options['#cache'] = false;

    // Режим работы задаётся администратором, но переопределяется извне.
    $options['mode'] = $ctx->get('mode', 'simple');

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
    if (!$this->checkPermission())
      return '<!-- Нет доступа к корзине (см. права на тип order) -->';

    return $this->dispatch(array($options['mode']), $options);
  }

  protected function onGetSimple(array $options)
  {
    $url = new url();

    $result = array(
      'mode' => 'simple',
      'content' => CartRPC::getCartContent(),
      'config' => mcms::modconf('cart'),
      );

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
    return Node::create('order')->checkPermission('c');
  }
};
