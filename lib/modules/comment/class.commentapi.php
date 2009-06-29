<?php

class CommentAPI
{
  /**
   * Возвращает список комментариев для ноды.
   */
  public static function on_get_list_xml(Context $ctx)
  {
    if (!($nid = $ctx->get('node')))
      throw new BadRequestException(t('Не указан идентификатор ноды (GET-параметр node).'));

    if (!$ctx->user->hasAccess(ACL::READ, 'comment'))
      throw new ForbiddenException();

    $nodes = Node::findXML(array(
      'class' => 'comment',
      'deleted' => 0,
      'published' => 1,
      'tags' => $nid,
      '#sort' => 'id',
      ), $ctx->db);

    return new Response(html::em('comments', $nodes), 'text/xml');
  }

  /**
   * Возвращает RSS комментариев.
   * @route GET//comments.rss
   */
  public static function on_get_rss(Context $ctx)
  {
    if (!class_exists('RSSFeed'))
      throw new PageNotFoundException(t('Модуль rss не установлен.'));

    $filter = array(
      'class' => 'comment',
      'deleted' => 0,
      'published' => 1,
      '#limit' => 20,
      '#sort' => '-id',
      );

    $title = t('Комментарии на %host', array(
      '%host' => MCMS_HOST_NAME,
      ));

    $feed = new RSSFeed($filter);
    return $feed->render($ctx, array(
      'title' => $title,
      'description' => $title . '.',
      'xsl' => os::path('lib', 'modules', 'comment', 'rss.xsl'),
      ));
  }

  /**
   * Возвращает количество комментариев.
   * @route GET//api/comment/count.xml
   */
  public static function on_count_comments(Context $ctx)
  {
    $result = '';

    if ($ids = explode(',', $ctx->get('node'))) {
      $params = array();
      if ($data = $ctx->db->getResults($sql = "SELECT n.id AS `id`, COUNT(*) AS `count` FROM node n INNER JOIN node__rel l ON l.tid = n.id INNER JOIN node c ON c.id = l.nid WHERE n.deleted = 0 AND n.published = 1 AND c.deleted = 0 AND c.published = 1 AND c.class = 'comment' AND `n`.`id` " . sql::in($ids, $params) . " GROUP BY n.id", $params))
        foreach ($data as $row)
          $result .= html::em('node', $row);
    }

    return new Response(html::em('counts', $result), 'text/xml');
  }
}
