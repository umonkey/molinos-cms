<?php

class TaxonomyAPI
{
  /**
   * Возвращает разделы, в которые можно помещать документы запрошенного типа.
   * Для типов документов всегда возвращает все разделы.
   */
  public static function on_get_enabled(Context $ctx)
  {
    $node = Node::load($ctx->get('node'), $ctx->db);

    $filter = array(
      'class' => 'tag',
      'deleted' => 0,
      );

    $options = array(
      'multiple' => true,
      );

    if ('type' != $node->class) {
      $type = Node::load(array(
        'class' => 'type',
        'name' => $node->class,
        'deleted' => 0,
        ), $ctx->db);
      $filter['tagged'] = $type->id;
      if (!in_array($node->class, $ctx->config->getArray('modules/taxonomy/multitagtypes')))
        unset($options['multiple']);
      $options['typeid'] = $type->id;
    }

    return new Response(html::em('nodes', $options, Node::findXML($filter, $ctx->db)), 'text/xml');
  }

  /**
   * Возвращает разделы, в которые помещён документ.
   */
  public static function on_get_selected(Context $ctx)
  {
    $xml = Node::findXML(array(
      'deleted' => 0,
      'class' => 'tag',
      'tagged' => $ctx->get('node'),
      ));
    return new Response(html::em('nodes', $xml), 'text/xml');
  }
}
