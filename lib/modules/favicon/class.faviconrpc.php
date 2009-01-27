<?php

class FaviconRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    return mcms::dispatch_rpc(__CLASS__, $ctx);
  }

  public static function rpc_get(Context $ctx)
  {
    $host = null;

    if ($tmp = $ctx->get('host'))
      $host = $tmp;
    elseif ($tmp = $ctx->get('url')) {
      $url = new url($tmp);
      $host = $url->host;
    }

    if (null === $host)
      throw new InvalidArgumentException(t('Имя сервера нужно указать в параметре host или url.'));

    $next = 'http://www.google.com/s2/favicons?domain=' . urlencode($host);
    $ctx->redirect($next);
  }
}