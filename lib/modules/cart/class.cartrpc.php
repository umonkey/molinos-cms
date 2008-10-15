<?php

class CartRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    mcms::debug($ctx);
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

  public static function getCartContent($items = array())
  {
    $result = array();

    $cart = self::getCart();

    if (!empty($cart)) {
      $sum = 0;
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

        $sum += $node->price * $qty;

        $result[] = array(
          'id' => $node->id,
          'name' => $node->name,
          'qty' => $qty,
          'price' => $node->price,
          'sum' => $node->price * $qty,
          );
      }

      $conf = mcms::modconf('cart');

      if (!empty($conf['discount_threshold'])) {
        if ($sum >= $conf['discount_threshold']) {
          if (!empty($conf['discount_price'])) {
            if ('%' == substr($price = $conf['discount_price'], -1))
              $price = $sum / 100 * substr($price, 0, -1);
            $result['discount'] = array(
              'name' => t('Скидка %size при заказе от %sum', array(
                '%size' => $conf['discount_price'],
                '%sum' => number_format($conf['discount_threshold'], 2, ',', '.'),
                )),
              'qty' => 1,
              'price' => -$price,
              'sum' => -$price,
              );
          }
        }
      }

      if (!empty($conf['delivery_threshold'])) {
        if (!empty($conf['delivery_price'])) {
          $result['delivery'] = array(
            'name' => t('Доставка (бесплатно при заказе от %size)', array(
              '%size' => number_format($conf['delivery_threshold'], 2, ',', '.'),
              )),
            'qty' => 1,
            'price' => ($sum < $conf['delivery_threshold'])
              ? $conf['delivery_price']
              : 0,
            );
          $result['delivery']['sum'] = $result['delivery']['price'];
        }
      }
    }

    mcms::session('cart', $cart);
    return $result;
  }

  public static function resetCart()
  {
    self::saveCart(array());
  }

  public static function rpc_add(Context $ctx)
  {
    if (null === ($id = $ctx->get('id')))
      throw new RuntimeException(t('Не указан id товара.'));

    $cart = self::getCart();

    if (empty($cart[$id]))
      $cart[$id] = $ctx->get('qty', 1);
    else
      $cart[$id] += $ctx->get('qty', 1);

    self::saveCart($cart);
  }

  public static function rpc_change(Context $ctx)
  {
    if (null === ($id = $ctx->get('id')))
      throw new RuntimeException(t('Не указан id товара.'));

    $cart = self::getCart();

    $cart[$id] = $ctx->get('qty', 1);

    self::saveCart($cart);
  }

  public static function rpc_reset(Context $ctx)
  {
    $cart = array();

    if ($ctx->method('post'))
      foreach ($ctx->post('item', array()) as $k => $v)
        $cart[$k] = $v;

    self::saveCart($cart);

    return self::goNext($ctx);
  }

  public static function rpc_redir(Context $ctx)
  {
    return self::goNext($ctx);
  }

  private static function getCart()
  {
    if (!is_array($cart = mcms::session('cart')))
      $cart = array();

    return $cart;
  }

  private static function saveCart(array $cart)
  {
    foreach ($cart as $k => $v)
      if (empty($v))
        unset($cart[$k]);

    mcms::session('cart', $cart);
  }

  private static function goNext(Context $ctx)
  {
    if ($ctx->method('post'))
      foreach ($ctx->post('next', array()) as $k => $v)
        if (!empty($v) and null !== $ctx->post($k))
          $ctx->redirect($v);
  }
}
