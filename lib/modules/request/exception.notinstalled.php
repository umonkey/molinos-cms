<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NotInstalledException extends UserErrorException
{
  public function __construct($message = null)
  {
    if ($message === null)
      $message = "Отсутствует как минимум одна жизненно важная таблица.&nbsp; Это больше всего похоже на то, что &laquo;Molinos.CMS&raquo; не была установлена.&nbsp; Если этот сайт ранее был работоспособен, обратитесь к его администратору за консультацией (возможно, произошло повреждение базы данных или обновления были применены некорректно).&nbsp; Возможно также, что этот сайт был только что проинсталлирован.";

    parent::__construct("Фатальная ошибка", 500, "Система не готова к использованию", $message);
  }
};
