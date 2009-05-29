<?php

class CloudAPI
{
  public static function on_get_list(Context $ctx)
  {
    if (!($st = $ctx->get('st')))
      throw new BadRequestException(t('Не указаны типы возвращаемых объектов (GET-параметр st).'));
    if (!($tt = $ctx->get('tt')))
      throw new BadRequestException(t('Не указаны типы связанных объектов (GET-параметр tt).'));
    if (!($limit = intval($ctx->get('limit'))))
      throw new BadRequestException(t('Не указано количество выводимых объектов (GET-параметр limit).'));

    if ($cache = intval($ctx->get('cache'))) {
      $ttl = floor(time() / $cache);
      $ckey = sprintf('cloud/%s/%s/%u/%u', $st, $tt, $limit, floor(time() / $cache));

      if ($cached = cache::getInstance()->$ckey)
        return new Response($cached, 'text/xml');
    }

    $params = array();
    $sql1 = sql::in(explode(' ', $st), $params);
    $sql2 = sql::in(explode(' ', $tt), $params);

    $data = $ctx->db->getResults($sql = 'SELECT n.id AS id, n.name AS name, '
      . 'COUNT(*) AS `cnt` '
      . 'FROM node n '
      . 'INNER JOIN node__rel r ON r.tid = n.id '
      . 'WHERE n.class ' . $sql1 . ' '
      . 'AND n.published = 1 '
      . 'AND n.deleted = 0 '
      . 'AND r.nid IN (SELECT id FROM node WHERE published = 1 AND deleted = 0 AND class ' . $sql2 . ') '
      . 'GROUP BY n.id, n.name '
      . 'ORDER BY cnt DESC LIMIT ' . $limit, $params);

    // Считаем общее количество объектов.
    $count = 0;
    foreach ($data as $item)
      $count += $item['cnt'];
    $percent = $count / 100;

    $result = '';
    foreach ($data as $item) {
      $p = round($item['cnt'] / $percent);
      $result .= html::em('item', array(
        'id' => $item['id'],
        'name' => trim($item['name']),
        'count' => $item['cnt'],
        'percent' => $p,
        'weight' => round($p / 10) + 1,
        ));
    }

    $xml = html::em('cloud', array(
      'total' => $count,
      ), $result);

    if ($cache)
      cache::getInstance()->$ckey = $xml;

    return new Response($xml, 'text/xml');
  }
}
