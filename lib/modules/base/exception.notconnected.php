<?php

class NotConnectedException extends InvalidArgumentException
{
  public function __construct($message = null)
  {
    if (null === $message)
      $message = t('Соединение с БД не установлено.');

    return parent::__construct($message);
  }
}
