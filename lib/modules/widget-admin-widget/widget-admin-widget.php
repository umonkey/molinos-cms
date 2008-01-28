<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class WidgetAdminWidget extends Widget implements iAdminWidget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);

    $this->groups = array(
      'Developers',
      );
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Конструктор',
      'description' => 'Управление доменами, страницами и виджетами, редактирование шаблонов.',
      );
  }

  // Препроцессор параметров.  Всё через урл.
  public function getRequestOptions(RequestContext $ctx)
  {
    return parent::getRequestOptions($ctx);
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    $result = array(
      'list' => array(),
      );

    // Формируем список доменов.
    foreach (Node::find(array('class' => 'domain', 'parent_id' => null)) as $node)
      $result['list'] = array_merge($result['list'], $node->getChildren('flat'));

    if (!$this->user->hasGroup('CMS Developers')) {
      foreach ($result['list'] as $k => $v) {
        if ($v['theme'] == 'admin')
          unset($result['list'][$k]);
      }
    }

    return $result;
  }

  // Отфильтровывает целочисленные значения.
  private static function filterIntegers(array $data)
  {
    $result = array();

    foreach ($data as $value)
      if (is_numeric($value))
        $result[] = $value;

    return $result;
  }
};
