<?php

class NodeAPI
{
  public static function get_xml(Context $ctx, $path, array $pathinfo)
  {
    $data = $ctx->db->fetch('SELECT `id`, `class`, `xml` FROM `node` WHERE `id` = ? AND `deleted` = 0 AND `published` = 1', array($ctx->get('id')));
    if (empty($data))
      throw new PageNotFoundException();
    $ctx->user->checkAccess('r', $data['class']);
    return new Response('<?xml version="1.0"?>' . $data['xml'], 'text/xml');
  }

  public static function get_parents_xml(Context $ctx)
  {
    return self::xml(html::em('nodes', Node::load($ctx->get('id'), $ctx->db)->getParentsXML()));
  }

  public static function list_xml(Context $ctx)
  {
    $filter = array(
      'deleted' => 0,
      'published' => 1,
      '#sort' => $ctx->get('sort', '-id'),
      '#limit' => $ctx->get('limit', 10),
      '#offset' => $ctx->get('skip', 0),
      );

    if ($tmp = $ctx->get('class'))
      $filter['class'] = self::split($tmp);
    if ($tmp = $ctx->get('tags'))
      $filter['tags'] = self::split($tmp);

    if ($tmp = $ctx->get('author'))
      $filter['uid'] = $tmp;

    foreach (explode(',', $ctx->get('filters')) as $f)
      if (!empty($f))
        $filter[$f] = $ctx->get($f);

    $attrs = array(
      'limit' => $filter['#limit'],
      'skip' => $filter['#offset'],
      );

    $output = Node::findXML($filter, $ctx->db);

    if ($ctx->get('pager')) {
      $attrs['total'] = Node::count($filter, $ctx->db);
      $attrs['prev'] = max($filter['#offset'] - $filter['#limit'], 0);

      if ($filter['#offset'] + $filter['#limit'] < $attrs['total'])
        $attrs['next'] = $filter['#offset'] + $filter['#limit'];

      // Вычисляем диапазон.
      $r1 = $filter['#offset'] + 1;
      $r2 = min($attrs['total'], $filter['#offset'] + $filter['#limit']);
      $attrs['range'] = $r1 . '-' . $r2;
    }

    return self::xml(html::em('nodes', $attrs, $output));
  }

  public static function get_sections_xml(Context $ctx)
  {
    $result = Node::findXML(array(
      'class' => 'tag',
      'published' => 1,
      'deleted' => 0,
      'tagged' => $ctx->get('id'),
      ), $ctx->db);

    return self::xml(html::em('nodes', $result));
  }

  private static function xml($xml)
  {
    if (!empty($xml))
      return new Response('<?xml version="1.0"?>' . $xml, 'text/xml');
    throw new PageNotFoundException();
  }

  private static function split($values)
  {
    $args = preg_split('/,/', $values, -1, PREG_SPLIT_NO_EMPTY);
    if (1 == count($args))
      return array_shift($args);
    return $args;
  }

  /**
   * Возвращает возможные действия для объекта.
   */
  public static function get_actions_xml(Context $ctx)
  {
    $result = '';
    $from = $ctx->get('from');

    foreach (Node::load($ctx->get('id'))->getActionLinks() as $k => $v) {
      if ($from)
        $v['href'] = str_replace('destination=CURRENT', 'destination=' . urlencode($from), $v['href']);
      $result .= html::em('action', array('name' => $k) + $v);
    }

    return self::xml(html::wrap('actions', $result));
  }

  /**
   * Возвращает описание объекта для предварительного просмотра.
   */
  public static function on_get_preview_xml(Context $ctx)
  {
    if (!($nid = $ctx->get('id')))
      throw new BadRequestException(t('Не указан идентификатор ноды (параметр id).'));

    $node = Node::load($nid);
    $result = $node->getPreviewXML($ctx);

    $options = array(
      'class' => $node->class,
      'editable' => $node->checkPermission('u'),
      'list' => $node->getListURL(),
      'nodename' => $node->getName(),
      );

    $options['typename'] = Node::load(array(
      'class' => 'type',
      'deleted' => 0,
      'name' => $node->class,
      ))->title;

    return self::xml(html::em('fields', $options, $result));
  }

  public static function on_get_related(Context $ctx)
  {
    $params = array($ctx->get('node'));
    $sql = "SELECT tid FROM node__rel WHERE nid = ? AND tid NOT IN (SELECT id FROM node WHERE deleted = 1)";

    if ($tmp = $ctx->get('key')) {
      $sql .= " AND `key` = ?";
      $params[] = $tmp;
    }

    $tags = $ctx->db->getResultsV("tid", $sql, $params);

    $filter = array(
      'deleted' => 0,
      'published' => 1,
      '#sort' => '-id',
      'tags' => $tags,
      '-id' => $ctx->get('node'),
      );
    if ($tmp = $ctx->get('class'))
      $filter['class'] = $tmp;
    $filter['#limit'] = $ctx->get('limit', 10);

    $xml = Node::findXML($filter, $ctx->db);

    return self::xml(html::em('nodes', $xml));
  }

  /**
   * Возвращает форму для создания документа.
   */
  public static function on_get_create_form(Context $ctx)
  {
    if (!($type = $ctx->get('type')))
      throw new BadRequestException(t('Не указан тип документа (GET-параметр type).'));

    $node = Node::create($type)->knock('c');
    $form = $node->formGet();

    return new Response($form->getXML(Control::data()), 'text/xml');
  }

  /**
   * Возвращает список доступных пользователю типов.
   */
  public static function on_get_create_types(Context $ctx)
  {
    $nodes = Node::findXML(array(
      'class' => 'type',
      'name' => $ctx->user->getAccess('c'),
      '-name' => $ctx->user->getAnonymous()->getAccess('c'),
      'published' => 1,
      '#sort' => 'name',
      ), $ctx->db);

    return new Response(html::em('nodes', $nodes), 'text/xml');
  }
}
