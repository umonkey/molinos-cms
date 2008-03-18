<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class ObjectNotFoundException extends UserErrorException
{
  public function __construct()
  {
    parent::__construct("Объект не найден", 404, "Объект не найден", "Вы попытались обратиться к объекту, который не удалось найти.");
  }
};
