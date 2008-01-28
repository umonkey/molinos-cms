<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class StructureAdminWidget extends Widget implements iAdminWidget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);

    $this->groups = array(
      'Structure Managers',
      );

  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Управление разделами',
      'description' => 'Позволяет редактировать структуру сайта, к которой потом привязывается контент.',
      );
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['root'] = $ctx->section_id;
    $options['search'] = $ctx->get('search');

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    $result = array(
      'sections' => TagNode::getTags('flat', array('search' => $options['search'])),
    );

    return $result;
  }
};
