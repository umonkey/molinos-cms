<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class FormWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Форма для создания документа',
      'description' => 'Возвращает форму для создания документа произвольного типа, если пользователь имеет на это право.',
      );
  }

  public static function formGetConfig()
  {
    $types = array();

    foreach (TypeNode::getSchema() as $k => $v)
      $types[$k] = $v['title'];

    asort($types);

    $types = array('*' => 'Предлагать выбор') + $types;

    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_section_default',
      'label' => t('Раздел по умолчанию'),
      'options' => TagNode::getTags('select'),
      'default' => t('Не используется'),
      )));

    $form->addControl(new EnumControl(array(
      'value' => 'config_type',
      'label' => t('Тип данных по умолчанию'),
      'options' => $types,
      'default' => t('Не используется'),
      'description' => "<p>Если пользователь попадает в раздел, в который он может добавить документы нескольких типов, ему будет предложен список этих типов в виде ссылок, ведущих на разные формы.&nbsp; Если вы укажете тип по умолчанию, и этот тип будет в списке возможных, вместо списка ссылок пользователю автоматически покажут нужную форму.</p>"
        ."<p>Однако <strong>имейте в виду</strong>, что этот параметр виджетом рассматривается как рекоммендация, а не как условие; если вы выберете здесь, скажем, &laquo;обратную связь&raquo;, а пользователь может создавать только &laquo;вакансии&raquo;, ему всё равно покажут форму с вакансией.</p>",
      )));

    $form->addControl(new BoolControl(array(
      'value' => 'config_stripped',
      'label' => t('Только базовые свойства'),
      'description' => t("При установке этого флага форма будет содержать только основные поля создаваемого документа, без дополнительных вкладок (вроде настроек доступа и файловых приложений).&nbsp; Это полезно для форм с обратной связью."),
      )));

    $form->addControl(new BoolControl(array(
      'value' => 'config_publish',
      'label' => t('Публиковать при создании'),
      )));

    $form->addControl(new TextLineControl(array(
      'value' => 'config_createlabel',
      'label' => t('Шаблон ссылки на форму'),
      'description' => t("Шаблон, по которому формируется текст ссылки на форму создания документа нужного типа.&nbsp; Используется только в случаях, когда конкретный тип не указан, и возможно создание нескольких разных документов."),
      )));

    $form->addControl(new BoolControl(array(
      'value' => 'config_anonymous',
      'label' => t('Не работать с типами, недоступными анонимному пользователю'),
      'description' => t("При установке этого флага форма будет содержать только основные поля создаваемого документа, без дополнительных вкладок (вроде настроек доступа и файловых приложений).&nbsp; Это полезно для форм с обратной связью."),
      )));

    $form->addControl(new TextLineControl(array(
      'value' => 'config_next',
      'label' => t('После сохранения переходить на'),
      'description' => t('Это значение используется если адрес перехода не указан явно через <tt>?destination</tt>.'),
      )));

    return $form;
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);
    
    $options['type'] = $ctx->get('type', $this->type);
    $options['default'] = $ctx->get('default', array());
    $options['#nocache'] = true;

    if (null === ($options['root'] = $ctx->section_id))
      $options['root'] = $this->section_default;

    if ('edit' == ($options['status'] = $ctx->get('status', 'default')))
      $options['node'] = $ctx->get('node');

    $options['stripped'] = empty($this->stripped) ? 0 : 1;

    if ((null !== ($tmp = $ctx->get('parent'))) and is_numeric($tmp))
      $options['parent_id'] = intval($tmp);
    else
      $options['parent_id'] = null;

    $this->options = $options;

    return $options;
  }

  public function onGet(array $options)
  {
    return $this->dispatch(array($options['status']), $options);
  }

  public function onGetEdit(array $options)
  {
    return parent::formRender('form-create-edit');
  }

  protected function onGetDefault(array $options)
  {
    $result = array();
    $types = $this->getTypeList($options['root']);

    // Если тип документа не указан, но доступен всего
    // один тип — используем его.
    if (!empty($options['type']) or 1 == count($types)) {
      if (!empty($options['type']))
        $type = $options['type'];
      else
        $type = array_shift(array_keys($types));

      $result['mode'] = 'form';
      $result['type'] = $type;
      $result['form'] = self::formRender('form-create-'. $type, null, 'form-create');
    }

    // Если типов несколько, и конкретный не указан — возвращаем
    // список, пусть пользователь выбирает.
    else {
      $result = array(
        'mode' => 'list',
        'list' => array(),
        );

      $url = new url();
      $key = $this->getInstanceName() .'.type';

      foreach ($types as $type => $v) {
        $url->setarg($key, $type);

        $schema = TypeNode::getSchema($type);

        $result['types'][$type] = array(
          'title' => $v,
          'description' => empty($schema['description'])
            ? '' : $schema['description'],
          'link' => strval($url),
          );
      }
    }

    return $result;
  }

  protected function onGetPending(array $options)
  {
    return array(
      'mode' => 'status',
      'status' => 'pending',
      'message' => t('Всё в порядке, документ сохранён, но на сайте он будет '
        .'виден только после одобрения модератором. Нужно немного подождать.'),
      );
  }

  // Возвращает список типов, доступных пользователю.
  private function getTypeList($root = null)
  {
    $types = array();

    // Список типов, разрешённых в этом разделе.
    $allowed = Node::find(array(
      'class' => 'type',
      'tags' => array($root),
      ));

    // Выбираем то, что может создать пользователь.
    foreach ($allowed as $t)
      if (mcms::user()->hasAccess('c', $t->name))
        $types[$t->name] = $t->title;

    return $types;
  }

  // РАБОТА С ФОРМАМИ.

  public function formGet($id)
  {
    $form = null;
    $types = $this->getTypeList($this->options['root']);

    if ($id == 'form-create-edit') {
      if (empty($this->options['node']))
        throw new PageNotFoundException();

      $node = Node::load($this->options['node']);

      if (!$node->checkPermission('u'))
        throw new ForbiddenException();

      return $node->formGet();
    }

    elseif ($id == 'form-create') {
      $form = new Form(array(
        'title' => t('Добавление объекта'),
        ));

      $form->addControl(new InfoControl(array(
        'text' => t('Выберите тип создаваемого документа.&nbsp; Этот список сформирован в соответствии с вашими правами.'),
        )));

      $form->addControl(new EnumRadioControl(array(
        'value' => 'node_content_class',
        'label' => t('Тип создаваемого документа'),
        'options' => $types,
        )));
      $form->addControl(new SubmitControl(array(
        'text' => t('Создать'),
        )));

      return $form;
    }

    elseif (false !== strstr($id, 'form-create-')) {
      if (!array_key_exists($type = substr($id, 12), $types))
        throw new PageNotFoundException();

      $node = $this->getNode($type);
      $form = $node->formGet($this->stripped);
      $form->wrapper_class = 'form-create-wrapper';

      $next = new url();
      $next->setarg($this->getInstanceName() .'.status', 'pending');

      $url = new url($form->action);
      $url->setarg('destination', mcms::path() .'/'. $next);

      $form->action = strval($url);

      $form->addControl(new HiddenControl(array(
        'value' => 'referer',
        )));

      if (!empty($this->options['default'])) {
        foreach (array_keys($this->options['default']) as $k) {
          $tmp = 'node_content_'. $k;

          $form->replaceControl($tmp, new HiddenControl(array(
            'value' => $tmp,
            )));
        }
      }
    }

    return $form;
  }

  private function getNode($class)
  {
    $data = $this->options['default'];

    if (empty($data['uid']))
      $data['uid'] = mcms::user()->id;

    $data['parent_id'] = $this->options['parent_id'];
    $data['published'] = $this->publish;

    $node = Node::create($class, $data);

    return $node;
  }

  public function formGetData($id)
  {
    if ($id == 'form-create-edit') {
      $node = Node::load($this->options['node']);
      return $node->formGetData();
    }

    elseif ($id == 'form-create') {
      $data = array();
    }

    elseif (substr($id, 0, 12) == 'form-create-') {
      $node = $this->getNode(substr($id, 12));
      $data = $node->formGetData();
    }

    $data['referer'] = empty($_SERVER['HTTP_REFERER']) ? null : $_SERVER['HTTP_REFERER'];

    return $data;
  }

  public function formProcess($id, array $data)
  {
    $next = null;

    switch ($id) {
    case 'form-create-edit':
      $node = Node::load($this->options['node']);
      $node->formProcess($data);
      $node->save();
      return $_GET['destination'];
    }

    return $next;
  }
};
