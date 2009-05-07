<?php

class NodeAPI
{
  public static function get_xml(Context $ctx, $path, array $pathinfo)
  {
    $data = $ctx->db->fetch('SELECT `xml` FROM `node` WHERE `id` = ?', array($ctx->get('id')));
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
      );

    if ($tmp = $ctx->get('class'))
      $filter['class'] = self::split($tmp);
    if ($tmp = $ctx->get('tags'))
      $filter['tags'] = self::split($tmp);

    $output = Node::findXML($ctx->db, $filter);

    return self::xml(html::wrap('nodes', $output));
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
}
