<?php

class RSSRouter
{
  public static function on_route_poll(Context $ctx)
  {
    $result = array();

    foreach ($ctx->config->getArray('modules/rss/feeds/export') as $name => $settings)
      if ('custom' != $name) {
        $settings['call'] = __CLASS__ . '::on_get_feed';
        $result['GET//' . $name . '.rss'] = $settings;
      }

    return $result;
  }

  public static function on_get_feed(Context $ctx, $name, array $settings)
  {
    $filter = array();
    if (!empty($settings['types']))
      $filter['class'] = $settings['types'];
    if (!empty($settings['tags']))
      $filter['tags'] = $settings['tags'];
    if (!empty($settings['author']))
      $filter['uid'] = $settings['author'];
    if (!empty($settings['limit']))
      $filter['#limit'] = $settings['limit'];

    $options = array();
    foreach (array('title', 'description', 'xsl') as $key)
      if (!empty($settings[$key]))
        $options[$key] = $settings[$key];

    $feed = new RSSFeed($filter);
    return $feed->render($ctx, $options);
  }

  public static function on_get_custom(Context $ctx)
  {
    $filter = array();
    if (!($filter['class'] = $ctx->get('type')))
      $filter['class'] = $ctx->db->getResultsV("name", "SELECT name FROM node WHERE class = 'type' AND deleted = 0 AND published = 1");
    if ($tmp = $ctx->get('tags'))
      $filter['tags'] = explode('+', $tmp);
    if ($tmp = $ctx->get('author'))
      $filter['uid'] = $tmp;

    $feed = new RSSFeed($filter);

    return $feed->render($ctx);
  }
}
