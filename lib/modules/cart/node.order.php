<?php

class OrderNode extends Node implements iContentType
{
  public function save()
  {
    if (empty($this->id))
      $this->orderdetails = CartRPC::getCartContent();
    elseif (array_key_exists('orderdetails', $this->olddata))
      $this->orderdetails = $this->olddata['orderdetails'];

    if (empty($this->orderdetails))
      throw new ForbiddenException(t('Не удалось оформить заказ: '
        .'ваша корзина пуста. Возможно, вы его уже оформили?'));

    $res = parent::save();

    CartRPC::resetCart();

    return $res;
  }
}
