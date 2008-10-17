<?php

class OrderNode extends Node implements iContentType
{
  public function save()
  {
    if ($isnew = (!$this->id)) {
      $this->orderdetails = CartRPC::getCartContent();
    } elseif (array_key_exists('orderdetails', $this->olddata)) {
      $this->orderdetails = $this->olddata['orderdetails'];
    }

    if (empty($this->orderdetails))
      throw new ForbiddenException(t('Не удалось оформить заказ: '
        .'ваша корзина пуста. Возможно, вы его уже оформили?'));

    $res = parent::save();

    if ($isnew)
      $this->sendInvoice();

    CartRPC::resetCart();

    return $res;
  }

  protected function sendInvoice()
  {
    if (empty($this->email))
      return;

    $result = $this->render(null, null, array(
      'mode' => 'invoice',
      'content' => $this->orderdetails,
      'details' => $this->data,
      ));

    if (!empty($result)) {
      $subject = t('Ваш заказ на %host', array('%host' => $_SERVER['HTTP_HOST']));
      BebopMimeMail::send(null, $this->email, $subject, $result);
    }
  }
}
