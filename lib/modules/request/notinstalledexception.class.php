<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NotInstalledException extends UserErrorException
{
  public function __construct($message = null)
  {
    if ($message === null) {
      $message = count(mcms::db()->getResults("SHOW TABLES LIKE 'node%'"))
        ? "Отсутствует как минимум одна жизненно важная таблица.&nbsp; Это больше всего похоже на то, что &laquo;Molinos.CMS&raquo; не была установлена.&nbsp; Если этот сайт ранее был работоспособен, обратитесь к его администратору за консультацией (возможно, произошло повреждение базы данных или обновления были применены некорректно).&nbsp; Возможно также, что этот сайт был только что проинсталлирован."
        : "Таблицы, используемые &laquo;Molinos.CMS&raquo;, в базе данных отсутствуют.&nbsp; Скорее всего, архив с кодом CMS был развёрнут в каталоге сайта, но инсталляционный скрипт запущен не был.&nbsp; Обратитесь к администратору сайта за объяснением, или, если попробуйте <a href='/install.php?destination=". urlencode($_SERVER['REQUEST_URI']) ."'>запустить инсталляционный скрипт</a> через веб-интерфейс.";
    }

    parent::__construct("Фатальная ошибка", 500, "Система не готова к использованию", $message);
  }
};
