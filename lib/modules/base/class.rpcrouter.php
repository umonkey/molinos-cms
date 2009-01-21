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
    $args = array($ctx);

    $response = mcms::invoke_module($this->query, 'iRemoteCall', 'hookRemoteCall', $args);

    if (true === $response)
      $response = $ctx->getRedirect();

    if (!($response instanceof Response))
      $response = new Response(t('Не удалось обработать запрос.'), 'text/plain', 404);

    return $response;
  }
}
