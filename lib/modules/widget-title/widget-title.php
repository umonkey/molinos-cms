<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TitleWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Названия разделов',
      'description' => 'Возвращает названия указанных в адресной строке разделов, в обратном порядке.&nbsp; Используется в основном для формирования заголовка страницы.',
      );
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['document_id'] = $ctx->document_id;
    $options['section_id'] = $ctx->section_id;

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    $result = array('list' => array());

    if (null !== ($tmp = $this->ctx->document))
      $result['list'][] = $tmp->getRaw();
    if (null !== ($tmp = $this->ctx->section))
      $result['list'][] = $tmp->getRaw();

    return $result;
  }
};
