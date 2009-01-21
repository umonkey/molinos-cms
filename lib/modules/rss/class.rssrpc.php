<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RSSRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    if (null === ($name = $ctx->get('feed')))
      throw new PageNotFoundException(t('Вы не указали имя RSS ленты: '
        .'?q=rss.rpc&feed=имя.'));

    $feed = Node::load(array('class' => 'rssfeed', 'name' => $name));

    if (!$feed->published)
      throw new ForbiddenException(t('Доступ к запрошенной ленте закрыт.'));

    $xml = $feed->getRSS($ctx);

    header('Content-Type: application/rss+xml; charset=utf-8');
    header('Content-Length: '. strlen($xml));

    die($xml);
  }
};
