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
      throw new PageNotFoundException(t('Не удалось обработать запрос: нет обработчика сообщения %msg.', array(
        '%msg' => $msg,
        )));

    elseif (true === $response)
      $response = $ctx->getRedirect();

    if (!($response instanceof Response)) {
      mcms::debug('Обработчик сообщения вернул что-то не то.', $msg, $response);
      throw new RuntimeException(t('Обработчик сообщения %msg вернул что-то не то.', array(
        '%msg' => $msg,
        )));
    }

    return $response;
  }
}
