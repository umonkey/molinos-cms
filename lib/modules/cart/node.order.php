<?php

class OrderNode extends Node implements iContentType
{
  public function save()
  {
    if ($isnew = (!$this->id)) {
      $cart = new Cart($ctx = Context::last());
      $this->orderdetails = $cart->getItems();
    } elseif (array_key_exists('orderdetails', (array)$this->olddata)) {
      $this->orderdetails = $this->olddata['orderdetails'];
    }

    if (empty($this->orderdetails))
      throw new ForbiddenException(t('Не удалось оформить заказ: '
        .'ваша корзина пуста. Возможно, вы его уже оформили?'));

    $res = parent::save();

    if ($isnew) {
      $this->sendEmail($this->email, 'invoice');
      $this->sendEmail($ctx->config->get('modules/cart/email'), 'notification');
      $cart->setItems(array());
    }

    return $res;
  }

  protected function sendEmail($to, $mode)
  {
    if (empty($to)) {
      mcms::flog($mode . ' not sent: email not found');
      return;
    }

    if (!($xslt = Context::last()->config->get("modules/cart/{$mode}_templates"))) {
      mcms::flog($mode . ' not sent: XSLT file not set');
      return;
    }

    if ($html = xslt::process($this->getXML(), $xslt)) {
      $subject = t('Заказ на %host', array('%host' => MCMS_HOST_NAME));
      BebopMimeMail::send(null, $to, $subject, $html);
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

  /**
   * Добавляет содержимое заказа в XML.
   */
  public function getExtraXMLContent()
  {
    $result = '';

    foreach ((array)$this->orderdetails as $k => $v)
      $result .= html::em('product', array(
        'id' => $k,
        'qty' => $v,
        ));

    return html::wrap('orderdetails', $result);
  }
}
