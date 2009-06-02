<?php

class NodeAPI
{
  public static function get_xml(Context $ctx, $path, array $pathinfo)
  {
    $data = $ctx->db->fetch('SELECT `xml` FROM `node` WHERE `id` = ? AND `deleted` = 0 AND `published` = 1', array($ctx->get('id')));
    if (empty($data))
      throw new PageNotFoundException();

    return new Response('<?xml version="1.0"?>' . $data, 'text/xml');
  }

  public static function get_parents_xml(Context $ctx)
  {
    return self::xml(html::em('nodes', NodeStub::create($ctx->get('id'), $ctx->db)->getParentsXML()));
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

    $attrs = array(
      'limit' => $filter['#limit'],
      'skip' => $filter['#offset'],
      );

    $output = Node::findXML($ctx->db, $filter);

    if ($ctx->get('pager')) {
      $attrs['total'] = Node::count($ctx->db, $filter);
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
    $result = Node::findXML($ctx->db, array(
      'class' => 'tag',
      'published' => 1,
      'deleted' => 0,
      'tagged' => $ctx->get('id'),
      ));

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
    $tags = $ctx->db->getResultsV("tid", "SELECT tid FROM node__rel WHERE nid = ? AND tid NOT IN (SELECT id FROM node WHERE deleted = 1)", array($ctx->get('node')));

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

    $xml = Node::findXML($ctx->db, $filter);

    return self::xml(html::em('nodes', $xml));
  }

  /**
   * Возвращает форму для создания документа.
   */
  public static function on_get_create_form(Context $ctx)
  {
    if (!($type = $ctx->get('type')))
      throw new BadRequestException(t('Не указан тип документа (GET-параметр type.)'));

    $node = Node::create($type)->knock('c');
    $form = $node->formGet();

    return new Response($form->getXML(), 'text/xml');
  }
}
