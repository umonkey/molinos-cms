<?php

class CartRPC
{
  public static function resetCart()
  {
    self::saveCart(array());
  }

  public static function rpc_add(Context $ctx)
  {
    if (null === ($id = $ctx->get('id')))
      throw new RuntimeException(t('Не указан id товара.'));

    $cart = new Cart($ctx);
    $items = $cart->getItems();

    if (empty($items[$id]))
      $items[$id] = $ctx->get('qty', 1);
    else
      $items[$id] += $ctx->get('qty', 1);

    $cart->setItems($items);

    return $ctx->getRedirect();
  }

  public static function rpc_add_many(Context $ctx)
  {
    $cart = new Cart($ctx);
    $items = $cart->getItems();

    foreach ($ctx->post('item') as $k => $v) {
      $current = isset($items[$k])
        ? $items[$k]
        : 0;
      $items[$k] = $current + intval($v);
    }

    $cart->setItems($items);

    return $ctx->getRedirect();
  }

  public static function rpc_change(Context $ctx)
  {
    if (null === ($id = $ctx->get('id')))
      throw new RuntimeException(t('Не указан id товара.'));

    $cart = new Cart($ctx);

    $items = $cart->getItems();
    $items[$id] = $ctx->get('qty', 1);
    $cart->setItems($items);

    return $ctx->getRedirect();
  }

  public static function rpc_reset(Context $ctx)
  {
    $cart = new Cart($ctx);

    $items = array();
    if ($ctx->method('post'))
      foreach ($ctx->post('item', array()) as $k => $v)
        if (!empty($v))
          $items[$k] = $v;

    $cart->setItems($items);

    return self::goNext($ctx);
  }

  public static function rpc_redir(Context $ctx)
  {
    return self::goNext($ctx);
  }

  private static function goNext(Context $ctx)
  {
    if ($ctx->method('post'))
      foreach ($ctx->post('next', array()) as $k => $v)
        if (!empty($v) and null !== $ctx->post($k))
          return new Redirect($v);
    return $ctx->getRedirect();
  }
}
