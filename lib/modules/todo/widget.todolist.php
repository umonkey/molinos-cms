<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class TodoListWidget extends Widget
{
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Список задач',
      'description' => 'Выводит общий список задач или задачи по конкретному объекту.',
      );
  }

  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['mode'] = $ctx->get('mode');

    if (null !== ($options['rel'] = $ctx->document_id))
      $options['relname'] = $ctx->document->name;
    else
      $options['relname'] = null;

    return $this->options = $options;
  }

  public function onGet(array $options)
  {
    $result = array(
      'list' => array(),
      'count' => 0,
      'rel' => $this->options['rel'],
      'relname' => $this->options['relname'],
      );

    $list = $this->getList();

    foreach ($list as $node) {
      $tmp = $node->getRaw();
      $tmp['__html'] = $node->render($this->getInstanceName());
      $result['list'][empty($node->closed) ? 'open' : 'closed'][] = $tmp;
      $result['count']++;
    }

    return $result;
  }

  private function getList()
  {
    $filter = array(
      'class' => 'todo',
      '#sort' => array(
        'updated' => 'desc',
        ),
      );

    if (!empty($this->options['rel']))
      $filter['tags'] = $this->options['rel'];

    return Node::find($filter);
  }
}
