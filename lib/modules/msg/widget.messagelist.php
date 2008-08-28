<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class MessageListWidget extends Widget
{
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Список личных сообщений',
      'description' => 'Возвращает список сообщений пользователя',
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_mode',
      'label' => t('Тип сообщений'),
      'required' => true,
      'options' => array(
        '' => t('по запросу'),
        'inbox' => t('входящие'),
        'sent' => t('исходящие'),
        ),
      'description' => t('В режиме «по запросу» режимом по умолчанию являются «входящие», переключение на «исходящие» осуществляется параметром ?виджет.mode=sent.'),
      )));

    $form->addControl(new NumberControl(array(
      'value' => 'config_limit',
      'label' => t('Количество сообщений'),
      'required' => true,
      'default' => 5,
      )));

    return $form;
  }

  protected function getRequestOptions(Context $ctx)
  {
    $options = array(
      'uid' => mcms::user()->id,
      'mode' => $this->mode ? $this->mode : $ctx->get('mode', 'inbox'),
      'limit' => $this->limit,
      );

    return $this->options = $options;
  }

  public function onGet(array $options)
  {
    $result = array('list' => array());

    $filter = array(
      'class' => 'message',
      're' => $options['uid'],
      'deleted' => 0,
      '#sort' => array(
        'id' => 'desc',
        ),
      );

    foreach (Node::find($filter, $options['limit']) as $n)
      $result['list'][] = $n->getRaw();

    $result['count'] = Node::count($filter);
    $result['left'] = $result['count'] - count($result['list']);

    return $result;
  }
};
