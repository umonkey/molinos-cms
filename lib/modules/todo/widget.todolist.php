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

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new TextLineControl(array(
      'value' => 'config_linktpl',
      'label' => t('Шаблон ссылки'),
      'default' => 'node/$nid',
      'required' => true,
      )));

    return $form;
  }

  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['uid'] = mcms::user()->id;
    $options['mode'] = $ctx->get('mode');

    if (null === ($options['rel'] = $ctx->document_id))
      $options['relname'] = null;
    elseif ('user' == $ctx->document->class)
      $options['relname'] = empty($ctx->document->fullname) ? $ctx->document->name : $ctx->document->fullname;
    else
      $options['relname'] = $ctx->document->name;

    return $this->options = $options;
  }

  public function onGet(array $options)
  {
    $result = array(
      'list' => array(),
      'count' => 0,
      'rel' => $this->options['rel'],
      'relname' => $this->options['relname'],
      'linktpl' => $this->linktpl,
      );

    $list = $this->getList();

    foreach ($list as $node) {
      $tmp = $node->getRaw();
      $result['list'][empty($node->closed) ? 'open' : 'closed'][] = $tmp;
      $result['count']++;
    }

    $result['users'] = $this->getUsers();

    return $result;
  }
  
  private function getUsers()
  {
    $result = array();
    
    foreach (Node::find(array('class' => 'user')) as $node)
      $result[$node->id] = empty($node->fullname) ? $node->name : $node->fullname;
      
    asort($result);
    
    return $result;
  }

  private function getList()
  {
    $filter = array(
      'class' => 'todo',
      '#sort' => array(
        'created' => 'asc',
        ),
      );

    if (!empty($this->options['rel']))
      $filter['tags'] = $this->options['rel'];

    return Node::find($filter);
  }
}
