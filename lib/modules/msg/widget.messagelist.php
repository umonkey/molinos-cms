<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class MessageListWidget extends Widget
{
  /**
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'class' => __CLASS__,
      'name' => 'Список личных сообщений',
      'description' => 'Возвращает список сообщений пользователя',
      );
  }

  public static function getConfigOptions()
  {
    return array(
      'mode' => array(
        'type' => 'EnumControl',
        'label' => t('Тип сообщений'),
        'required' => true,
        'options' => array(
          '' => t('по запросу'),
          'inbox' => t('входящие'),
          'sent' => t('исходящие'),
          ),
        'description' => t('В режиме «по запросу» режимом по умолчанию являются «входящие», переключение на «исходящие» осуществляется параметром ?виджет.mode=sent.'),
        ),
      'limit' => array(
        'type' => 'NumberControl',
        'label' => t('Количество сообщений'),
        'required' => true,
        'default' => 5,
        ),
      );
  }

  protected function getRequestOptions(Context $ctx)
  {
    $options = array(
      'uid' => Context::last()->user->id,
      'mode' => $this->mode ? $this->mode : $this->get('mode', 'inbox'),
      'limit' => $this->limit,
      );

    return $options;
  }

  public function onGet(array $options)
  {
    $result = array('list' => array());

    $filter = array(
      'class' => 'message',
      're' => $options['uid'],
      'deleted' => 0,
      '#sort' => '-id',
      );

    foreach (Node::find($filter, $options['limit']) as $n)
      $result['list'][] = $n->getRaw();

    $result['count'] = Node::count($filter);
    $result['left'] = $result['count'] - count($result['list']);

    return $result;
  }
};
