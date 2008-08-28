<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

require(dirname(__FILE__) .'/lib/bootstrap.php');

// Проверка окружения.  Если что не так — выводит сообщение об ошибке.
mcms::check();

try {
  $req = new RequestController();
  $output = $req->run();
} catch (UserErrorException $e) {
  if ($e->getCode()) {
    // Ошибка 404 — пытаемся использовать подстановку.
    if (404 == $e->getCode()) {
      try {
        $new = mcms::db()->getResult("SELECT `new` FROM `node__fallback` "
          ."WHERE old = ?", array($_SERVER['REQUEST_URI']));
        if (!empty($new))
          mcms::redirect($new, 302);
      } catch (Exception $e2) { }
    }

    // Пытаемся вывести страницу /$статус
    $req = new RequestController(new Context(array(
      'url' => $e->getCode(),
      )));
    $output = $req->run();

    header(sprintf('HTTP/1.1 %s Error', $e->getCode()));
  } else {
    throw $e;
  }
}

// TODO: вынести сюда профайлер, или может вообще его в отдельный модуль, на
// iRequestHook посадить?

header('Content-Length: '. strlen($output));
die($output);
