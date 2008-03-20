<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class ValidationError extends UserErrorException
{
  public function __construct($name, $message = null)
  {
    if ($message === null)
      $message = "Вы не заполнили поле &laquo;{$name}&raquo;, которое нужно заполнить обязательно.&nbsp; Пожалуйста, вернитесь назад и проверьте введённые данные.";

    parent::__construct("Ошибка ввода данных", 400, "Ошибка в поле <span class='highlight'>&laquo;{$name}&raquo;</span>", $message);
  }
};
