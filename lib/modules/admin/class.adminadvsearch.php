<?php

class AdminAdvSearch
{
  /**
   * Вывод поисковой формы.
   */
  public static function on_get_search_form(Context $ctx)
  {
    $output = '';

    $url = new url($ctx->get('destination', $ctx->get('from')));

    if (null === $url->arg('preset')) {
      $types = Node::find(array(
        'class' => 'type',
        'published' => 1,
        'deleted' => 0,
        'name' => $ctx->user->getAccess(ACL::READ),
        ), $ctx->db);

      $list = array();
      foreach ($types as $type)
        if (!$type->isdictionary)
          $list[$type->name] = $type->title;

      asort($list);

      if ('file' == ($type = $ctx->get('type')) and array_key_exists($type, $list))
        $list = array(
          $type => $type,
          );

      $tmp = '';
      foreach ($list as $k => $v)
        $tmp .= html::em('type', array(
          'name' => $k,
          'title' => $v,
          ));
      $output .= html::em('types', $tmp);
    }

    $tmp = '';
    foreach (Node::getSortedList('user', 'fullname', 'id') as $k => $v)
      $tmp .= html::em('user', array(
        'id' => $k,
        'name' => $v,
        ));
    $output .= html::em('users', $tmp);

    if (null === $url->arg('preset')) {
      $tmp = '';
      foreach (Node::getSortedList('tag', 'id', 'name') as $k => $v)
        $tmp .= html::em('section', array(
          'id' => $k,
          'name' => $v,
          ));
      $output .= html::em('sections', $tmp);
    }

    return html::em('content', array(
      'name' => 'search',
      'query' => self::get_clean_query($url->arg('search')),
      'from' => urlencode($ctx->get('from')),
      ), $output);
  }

  /**
   * Поиск (обработка).
   */
  public static function on_post_search_form(Context $ctx)
  {
    $list = 'admin/content/list';
    $term = $ctx->post('search_term');

    if (null !== ($tmp = $ctx->post('search_class')))
      $list = Node::create($tmp)->getListURL();

    $url = new url($ctx->get('from', $list));

    if (null !== ($tmp = $ctx->post('search_uid')))
      $url->setarg('author', $tmp);

    if (null !== ($tmp = $ctx->post('search_tag')))
      $term .= ' tags:' . $tmp;

    $url->setarg('search', trim($term));
    $url->setarg('page', null);

    $ctx->redirect($url->string());
  }

  /**
   * Очищает поисковый запрос от мусора.
   */
  private static function get_clean_query($query)
  {
    $parts = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

    $new = array();
    foreach ($parts as $part)
      if (0 !== strpos($part, 'tags:'))
        $new[] = $part;

    return implode(' ', $new);
  }
}
