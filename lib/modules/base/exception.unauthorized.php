<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class UnauthorizedException extends UserErrorException
{
  public function __construct($message = null)
  {
    if (empty($message))
      $message = 'Требуется авторизация.';

    parent::__construct($message, 401);
  }
}
