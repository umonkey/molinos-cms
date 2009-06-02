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

    if (!$ctx->user->hasAccess('r', 'comment'))
      throw new ForbiddenException();

    $nodes = Node::findXML($ctx->db, array(
      'class' => 'comment',
      'deleted' => 0,
      'tags' => $nid,
      '#sort' => 'id',
      ));

    return new Response(html::em('comments', $nodes), 'text/xml');
  }
}
