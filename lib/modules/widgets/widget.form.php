<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class FormWidget extends Widget
{
  private $options;

  /**
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Форма для создания документа',
      'description' => 'Выводит форму для создания документа.',
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/FormWidget',
      );
  }

  public static function getConfigOptions(Context $ctx)
  {
    $types = array('*' => 'Предлагать выбор')
      + Node::getSortedList('type', 'title', 'name');

    return array(
      'section_default' => array(
        'type' => 'EnumControl',
        'label' => t('Раздел по умолчанию'),
        'options' => Node::getSortedList('tag'),
        'default' => t('Не используется'),
        ),
      'type' => array(
        'type' => 'EnumControl',
        'label' => t('Тип данных по умолчанию'),
        'options' => $types,
        'default' => t('Не используется'),
        'description' => "<p>Если пользователь попадает в раздел, в который он может добавить документы нескольких типов, ему будет предложен список этих типов в виде ссылок, ведущих на разные формы.&nbsp; Если вы укажете тип по умолчанию, и этот тип будет в списке возможных, вместо списка ссылок пользователю автоматически покажут нужную форму.</p>"
          ."<p>Однако <strong>имейте в виду</strong>, что этот параметр виджетом рассматривается как рекоммендация, а не как условие; если вы выберете здесь, скажем, &laquo;обратную связь&raquo;, а пользователь может создавать только &laquo;вакансии&raquo;, ему всё равно покажут форму с вакансией.</p>",
        ),
      'stripped' => array(
        'type' => 'BoolControl',
        'label' => t('Только базовые свойства'),
        'description' => t("При установке этого флага форма будет содержать только основные поля создаваемого документа, без дополнительных вкладок (вроде настроек доступа и файловых приложений).&nbsp; Это полезно для форм с обратной связью."),
        ),
      'publish' => array(
        'type' => 'BoolControl',
        'label' => t('Публиковать при создании'),
        ),
      'createlabel' => array(
        'type' => 'TextLineControl',
        'label' => t('Шаблон ссылки на форму'),
        'description' => t("Шаблон, по которому формируется текст ссылки на форму создания документа нужного типа.&nbsp; Используется только в случаях, когда конкретный тип не указан, и возможно создание нескольких разных документов."),
        ),
      'anonymous' => array(
        'type' => 'BoolControl',
        'label' => t('Не работать с типами, недоступными анонимному пользователю'),
        ),
      'next' => array(
        'type' => 'TextLineControl',
        'label' => t('После сохранения переходить на'),
        'description' => t('Это значение используется если адрес перехода не указан явно через <tt>?destination</tt>.'),
        ),
      );
  }

  // Препроцессор параметров.
  public function getRequestOptions(Context $ctx, array $params)
  {
    $options = parent::getRequestOptions($ctx, $params);
    $options['type'] = $this->get('type', $this->type);
    $options['default'] = $this->get('default', array());
    $options['#cache'] = false;

    if (null === ($options['root'] = $params['section']['id']))
      $options['root'] = $this->section_default;

    if ('default' != ($options['status'] = $this->get('status', 'default')))
      $options['node'] = $this->get('node');

    $options['stripped'] = empty($this->stripped) ? 0 : 1;

    if ((null !== ($tmp = $this->get('parent'))) and is_numeric($tmp))
      $options['parent_id'] = intval($tmp);
    else
      $options['parent_id'] = null;

    return $this->options = $options;
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

    if (empty($types))
      return '<!-- no available types -->';

    // Запрошенный тип недоступен.
    if (!empty($options['type']) and !array_key_exists($options['type'], $types)) {
      try {
        Node::load(array(
          'class' => 'type',
          'name' => $options['type'],
          ));
        throw new ForbiddenException(t('Вам нельзя добавлять документы этого типа в этот раздел.'));
      } catch (ObjectNotFoundException $e) {
        throw new ForbiddenException(t('Тип документа «%name» мне не известен.', array(
          '%name' => $options['type'],
          )));
      }
    }

    // Выводим форму добавления документа.
    if (!empty($options['type']) or count($types) == 1) {
      $node = Node::create(empty($options['type'])
        ? array_shift(array_keys($types))
        : $options['type']);
      return $node->formGet()->getXML($node);
    }

    // Выводим список типов документов.
    return html::em('allowedTypes', html::simpleOptions($types));
  }

  protected function onGetPending(array $options)
  {
    try {
      $node = Node::load($options['node']);
    } catch (ObjectNotFoundException $e) {
      $node = null;
    }

    return array(
      'mode' => 'status',
      'status' => 'pending',
      'message' => t('Всё в порядке, документ сохранён, но на сайте он будет '
        .'виден только после одобрения модератором. Нужно немного подождать.'),
      'node' => empty($node) ? null : $node->getRaw(),
      );
  }

  // Возвращает список типов, доступных пользователю.
  private function getTypeList($root = null)
  {
    $types = array();

    $filter = array(
      'class' => 'type',
      );

    if (!empty($root))
      $filter['tags'] = $root;

    $allowed = Node::find($filter, $this->ctx->db);

    $atypes = $this->getAllowedTypes();

    // Выбираем то, что может создать пользователь.
    foreach ($allowed as $t)
      if (in_array($t->name, $atypes))
        $types[$t->name] = $t->title;

    return $types;
  }

  private function getAllowedTypes()
  {
    $u = context::last()->user;

    if ($this->anonymous)
      $u = $u->getAnonymous();

    return $u->getAccess(ACL::CREATE);
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

      if (!$node->checkPermission(ACL::UPDATE))
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
        'value' => 'class',
        'label' => t('Тип создаваемого документа'),
        'options' => $types,
        )));
      $form->addControl(new SubmitControl(array(
        'text' => t('Создать'),
        )));

      return $form;
    }

    elseif (false !== strstr($id, 'form-create-')) {
      if (!array_key_exists($type = substr($id, 12), $types)) {
        $schema = Schema::load($this->ctx->db, $type);

        if (empty($schema['id']))
          throw new PageNotFoundException(t('Тип документа «%name» '
            .'мне не известен, создать его невозможно.', array(
              '%name' => $type,
              )));
        else
          throw new ForbiddenException(t('Вы не можете создать документ '
            .'типа «%name», извините.', array(
              '%name' => $type,
              )));
      }

      $node = $this->getNode($type);
      $form = $node->formGet($this->stripped);
      $form->wrapper_class = 'form-create-wrapper';

      $form->addControl(new HiddenControl(array(
        'value' => 'referer',
        )));

      /*
      if (!empty($this->options['default'])) {
        foreach (array_keys($this->options['default']) as $k) {
          $tmp = $k;

          $form->replaceControl($tmp, new HiddenControl(array(
            'value' => $tmp,
            )));
        }
      }
      */
    }

    return $form;
  }

  private function getNode($class)
  {
    $data = $this->options['default'];

    if (empty($data['uid']))
      $data['uid'] = Context::last()->user->id;

    $data['parent_id'] = $this->options['parent_id'];
    $data['published'] = $this->publish;

    if (!empty($this->options['default']))
      foreach ($this->options['default'] as $k => $v)
        $data[$k] = $v;

    $node = Node::create($class, $data);

    return $node;
  }

  public function formGetData($id)
  {
    if ($id == 'form-create-edit') {
      $node = Node::load($this->options['node']);
      return $node;
    }

    elseif ($id == 'form-create') {
      $data = array();
    }

    elseif (substr($id, 0, 12) == 'form-create-') {
      $node = $this->getNode(substr($id, 12));
      $data = $node;
    }

    $data->referer = empty($_SERVER['HTTP_REFERER']) ? null : $_SERVER['HTTP_REFERER'];

    return $data;
  }
};
