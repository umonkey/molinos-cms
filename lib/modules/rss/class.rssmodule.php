<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RSSModule implements iRemoteCall, iAdminMenu, iAdminUI
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    if (null === ($name = $ctx->get('feed')))
      throw new PageNotFoundException(null, null, t('Вы не указали имя RSS потока: /rss.rpc?feed=имя.'));

    $feed = Node::load(array('class' => 'rssfeed', 'name' => $name));

    if (!$feed->published)
      throw new PageNotFoundException();

    $xml = $feed->getRSS();

    header('Content-Type: application/rss+xml; charset=utf-8');
    header('Content-Length: '. strlen($xml));

    die($xml);
  }

  private static function getItems(Node $feed)
  {
    $filter = array(
      'published' => 1,
      'class' => preg_split('/, */', $feed->types),
      );

    foreach (explode(' ', $feed->sort) as $field) {
      if (substr($field, 0, 1) == '-') {
        $mode = 'desc';
        $field = substr($field, 1);
      } else {
        $mode = 'asc';
      }

      $filter['#sort'][$field] = $mode;
    }

    return Node::find($filter, $feed->limit);
  }

  private static function formatFeed(Node $feed, array $items)
  {
    $output = '<?xml version="1.0" encoding="utf-8" ?>';
    $output .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">';
    $output .= '<channel>';
    $output .= '<title>'. mcms_plain($node->title) .'</title>';
    $output .= '<description>'. mcms_plain($node->description) .'</description>';

    if (isset($feed->link)) {
      $output .= '<link>'. $feed->link .'</link>';
      $output .= '<atom:link href=\'http://'. $_SERVER['HTTP_HOST'] .'/rss.rpc?feed='. $feed->name .'\' rel=\'self\' type=\'application/rss+xml\' />';
    }

    if (isset($feed->description))
      $output .= '<description>'. mcms_plain($feed->description) .'</description>';

    if (isset($feed->language))
      $output .= '<language>'. $feed->language .'</language>';

    $output .= '<pubDate>'. date('r', strtotime($items[key($items)]->created)) .'</pubDate>';
    $output .= '<generator>http://molinos-cms.googlecode.com/</generator>';

    $output .= self::formatItems($items);

    $output .= '</channel></rss>';

    return $output;
  }

  public static function getMenuIcons()
  {
    $icons = array();

    if (mcms::user()->hasGroup('Comment Managers'))
      $icons[] = array(
        'group' => 'content',
        'href' => '/admin/?module=rss',
        'title' => t('RSS потоки'),
        'description' => t('Управление экспортируемыми данными.'),
        );

    return $icons;
  }

  public static function onGet(RequestContext $ctx)
  {
    $tmp = new RSSListHandler($ctx);
    return $tmp->getHTML();
  }
};
