<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class BlogWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Блоги',
      'description' => 'Используется для отображения сводной ленты блогов и отдельных лент пользователей.',
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new TextLineControl(array(
      'value' => 'config_limit',
      'label' => t('Количество записей на странице'),
      )));

    return $form;
  }

  public function formHookConfigData(array &$data)
  {
    // $data['xyz'] = 123;
  }

  public function formHookConfigSaved()
  {
    $this->installTypes();
  }

  // Препроцессор параметров.
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    if ($ctx->section->id or $ctx->document->id) {
      mcms::debug("Виджет {$this->getInstanceName()} не может работать на страницах, параметризуемых кодом раздела или документа.");
      throw new WidgetHaltedException();
    }

    if (count($ctx->apath) == 1)
      $options['user'] = $ctx->apath[0];
    elseif (count($ctx->apath) > 1)
      throw new PageNotFoundException();
    else
      $options['user'] = null;

    $options['limit'] = $this->limit ? $this->limit : 10;
    $options['page'] = $ctx->get('page', 1);

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    $result = array();
    $filter = array('class' => 'blog', 'published' => 1, '#sort' => array('id' => 'desc'));

    if (null !== $options['user']) {
      $user = array_pop(Node::find(array('class' => 'user', 'published' => 1, 'login' => $options['user'])));

      if (empty($user))
        throw new PageNotFoundException();

      $result['user'] = $user->getRaw();

      if (array_key_exists('password', $result['user']))
        unset($result['user']['password']);

      $filter['uid'] = $user->id;
    }

    $total = Node::count($filter);

    $result['pager'] = parent::getPager($total, $options['page'], $options['limit']);

    foreach (Node::find($filter, $options['limit'], $options['limit'] * ($options['page'] - 1)) as $post)
      $result['documents'][] = $post->getRaw();

    return $result;
  }

  private function installTypes()
  {
    if (!Node::count(array('class' => 'type', 'name' => 'blog'))) {
      $type = Node::create('type', array(
        'name' => 'blog',
        'title' => t('Запись в дневнике'),
        'description' => t('Ваш дневник — ваше личное пространство на этом сервере.  Можете писать здесь всё, что угодно. Другие пользователи смогут это читать и комментировать, но на главную страницу эта информация не попадёт.  Туда попадают только статьи.'),
        'fields' => array(
          'name' => array(
            'type' => 'TextLineControl',
            'label' => t('Заголовок'),
            'required' => true,
            ),
          'teaser' => array(
            'type' => 'TextAreaControl',
            'label' => t('Краткое содержание'),
            ),
          'body' => array(
            'type' => 'TextHTMLControl',
            'label' => t('Текст записи'),
            'required' => true,
            ),
          'picture' => array(
            'type' => 'AttachmentControl',
            'label' => t('Иллюстрация'),
            ),
          ),
        ));
      $type->save();
    }
  }
};
