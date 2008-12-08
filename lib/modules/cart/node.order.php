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

    if ($isnew) {
      $this->sendEmail($this->email, 'invoice');
      $this->sendEmail(mcms::modconf('cart', 'email'), 'notification');
    }

    CartRPC::resetCart();

    return $res;
  }

  protected function sendEmail($to, $mode)
  {
    if (empty($to)) {
      mcms::flog('cart', $mode . ' not sent: email not found');
      return;
    }

    $result = $this->render(null, null, array(
      'mode' => $mode,
      'content' => $this->orderdetails,
      'details' => $this->data,
      ));

    if (!empty($result)) {
      $subject = t('Заказ на %host', array('%host' => $_SERVER['HTTP_HOST']));
      BebopMimeMail::send(null, $to, $subject, $result);
    }
  }

  public static function getDefaultSchema()
  {
    return array(
      'orderdetails' => array(
        'type' => 'OrderDetailsControl',
        'label' => t('Содержимое заказа'),
        'volatile' => true,
        ),
      );
  }

  public function getFormTitle()
  {
    return $this->id
      ? t('Информация о заказе от %email', array('%email' => $this->email))
      : t('Добавление нового заказа');
  }
}
