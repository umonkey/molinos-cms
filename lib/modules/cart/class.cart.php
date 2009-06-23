<?php

class Cart
{
  private $ctx;

  public function __construct(Context $ctx)
  {
    $this->ctx = $ctx;
  }

  /**
   * Возвращает содержимое корзины в виде массива.
   */
  public function getItems()
  {
    if (!is_array($cart = mcms::session('cart.' . $this->ctx->user->id)))
      $cart = array();
    return $cart;
  }

  /**
   * Сохраняет содержимое в сессии.
   */
  public function setItems(array $items)
  {
    foreach ($items as $k => $v) {
      if (empty($v))
        unset($items[$k]);
      elseif (!is_numeric($v))
        throw new BadRequestException(t('Количество должно быть числом.'));
      elseif (!is_numeric($k))
        throw new BadRequestException(t('Идентификатор товара должен быть числом.'));
    }

    mcms::session('cart.' . $this->ctx->user->id, $items);
  }

  /**
   * Возвращает содержимое корзины в виде XML.
   */
  public function getXML($details = true)
  {
    $result = array();

    $sumqty = 0;
    if (count($cart = $this->getItems())) {
      $sum = $sumqty = 0;
      $ids = array_keys($cart);

      foreach ($nodes = Node::find(array('id' => $ids)) as $node) {
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
        $sumqty += $qty;

        $result[] = array(
          'id' => $node->id,
          'name' => $node->getName(),
          'qty' => $qty,
          'price' => $node->price,
          'sum' => $node->price * $qty,
          );
      }

      $total = $sum;

      $conf = $this->ctx->config->getArray('modules/cart');

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

            $total -= $price;
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

          $total += $result['delivery']['sum'];
        }
      }
    }

    if ($discounter = $this->ctx->config->get('modules/cart/discounter')) {
      if (class_exists($discounter)) {
        $d = new $discounter();
        $d->process($result);
      }
    }

    $result['total'] = array(
      'name' => t('Итого'),
      'qty' => $sumqty,
      'price' => null,
      'sum' => 0,
      );

    mcms::session('cart', $cart);

    $output = '';
    foreach ($result as $k => $v) {
      if (is_numeric($k)) {
        $result['total']['sum'] += $v['sum'];
        $output .= html::em('item', $v);
      }
    }

    $output = html::em('items', $result['total'], $output);
    if ($details)
      $output .= html::wrap('details', Node::findXML(array(
        'deleted' => 0,
        'published' => 1,
        'id' => array_keys($cart),
        )));

    return $output;
  }

  /**
   * Возвращает настройки в XML.
   */
  public function getConfigXML()
  {
    $result = '';

    /*
    if (is_array($tmp = $this->ctx->config->cart)) {
      mcms::debug($tmp);
    }
    */

    return html::wrap('config', $result);
  }
}
