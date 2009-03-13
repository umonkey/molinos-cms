<?php

class RPCRouter implements iRequestRouter
{
  protected $query;

  public function __construct($query)
  {
    $this->query = substr($query, 0, -4);
  }

  public function route(Context $ctx)
  {
    $response = $ctx->registry->unicast($msg = 'ru.molinos.cms.rpc.' . $this->query, array($ctx));

    if (false === $response)
      $response = new Response(t('Не удалось обработать запрос: нет обработчика сообщения %msg.', array(
        '%msg' => $msg,
        )), 'text/plain', 404);

    elseif (true === $response)
      $response = $ctx->getRedirect();

    if (!($response instanceof Response))
      $response = new Response(t('Обработчик сообщения %msg вернул что-то не то.', array(
        '%msg' => $msg,
        )), 'text/plain', 404);

    return $response;
  }
}
