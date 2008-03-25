<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class BadRequestException extends UserErrorException
{
  public function __construct()
  {
    parent::__construct("Неверный запрос", 400, "Неверный запрос", "Запрос к серверу сформулирован неверно.");
  }
};
