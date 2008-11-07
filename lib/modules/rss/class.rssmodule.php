<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RSSModule implements iRemoteCall, iAdminMenu, iAdminUI
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

  public static function getMenuIcons()
  {
    $icons = array();

    if (mcms::user()->hasAccess('u', 'rssfeed'))
      $icons[] = array(
        'group' => 'structure',
        'href' => '?q=admin&module=rss',
        'title' => t('RSS ленты'),
        'description' => t('Управление экспортируемыми данными.'),
        );

    return $icons;
  }

  public static function onGet(Context $ctx)
  {
    $tmp = new RSSListHandler($ctx);
    return $tmp->getHTML();
  }
};
