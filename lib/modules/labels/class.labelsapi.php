<?php

class LabelsAPI
{
  public static function on_find_xml(Context $ctx)
  {
    if (null === ($search = $ctx->get('search')))
      throw new BadRequestException(t('Не указана подстрока для поиска (GET-параметр search).'));

    if (!($limit = intval($ctx->get('limit', 5))))
      throw new BadRequestException(t('Не указано количество возовращаемых меток (GET-параметр limit).'));

    list($sql, $params) = Query::build(array(
      'class' => 'label',
      'deleted' => 0,
      'published' => 1,
      'name?|' => '%' . $search . '%',
      '#sort' => 'name',
      ))->getSelect(null, null, 'name');

    $result = '';
    foreach ((array)$ctx->db->getResultsV("name", $sql, $params) as $name)
      $result .= html::em('label', html::cdata($name));

    return new Response(html::em('labels', array(
      'search' => $search,
      'limit' => $limit,
      ), $result), 'text/xml');
  }
}
