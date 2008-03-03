<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class FormWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);

    $this->groups = array('Visitors');
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
      'default' => t('Всегда предлагать выбор'),
      'description' => "<p>Если пользователь попадает в раздел, в который он может добавить документы нескольких типов, ему будет предложен список этих типов в виде ссылок, ведущих на разные формы.&nbsp; Если вы укажете тип по умолчанию, и этот тип будет в списке возможных, вместо списка ссылок пользователю автоматически покажут нужную форму.</p>"
        ."<p>Однако <strong>имейте в виду</strong>, что этот параметр виджетом рассматривается как рекоммендация, а не как условие; если вы выберете здесь, скажем, &laquo;обратную связь&raquo;, а пользователь может создавать только &laquo;вакансии&raquo;, ему всё равно покажут форму с вакансией.</p>",
      )));

    $form->addControl(new BoolControl(array(
      'value' => 'config_stripped',
      'label' => t('Только базовые свойства'),
      'description' => t("При установке этого флага форма будет содержать только основные поля создаваемого документа, без дополнительных вкладок (вроде настроек доступа и файловых приложений).&nbsp; Это полезно для форм с обратной связью."),
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

    if (null === ($options['root'] = $ctx->section_id))
      $options['root'] = $this->section_default;

    $options['status'] = $ctx->get('status', 'default');
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

  protected function onGetDefault(array $options)
  {
    $output = '';
    $types = $this->getTypeList($options['root']);

    if (empty($options['type']) and count($types) == 1)
      $options['type'] = key($types);

    if (!empty($options['type'])) {
      if (!array_key_exists($options['type'], $types))
        return null;

      $output = "<div class='form-widget-wrapper type-{$options['type']}-form'>". self::formRender('form-create-'. $options['type']) ."</div>";
    } elseif (!empty($types)) {
      $output = '<div class=\'type-selection-form\'>';
      $output .= '<h2>'. t('Документ какого типа вы хотите создать?') .'</h2>';
      $output .= '<dl>';

      foreach ($types as $k => $v) {
        $output .= '<dt>'. t('<a href=\'@link\'>%title</a>', array(
          '@link' => mcms_url(array('args' => array($this->getInstanceName() => array(
            'type' => $k,
            )))),
          '%title' => $v,
          )) .'</dt>';

        $schema = TypeNode::getSchema($k);
        $description = empty($schema['description']) ? t('Описание отсутствует.') : $schema['description'];

        $output .= '<dd>'. $description .'</dd>';
      }

      $output .= '</dl>';
      $output .= '</div>';

      // $output = self::formRender('form-create');
    }

    return array('html' => $output);
  }

  protected function onGetPending(array $options)
  {
    $output = '<h2>'. t('Документ создан') .'</h2>';
    $output .= '<p>'. t('Всё в порядке, документ сохранён, но на сайте он будет виден только после одобрения модератором. Нужно немного подождать.') .'</p>';
    $output .= '<p>'. t('Хотите <a href=\'@url\'>создать ещё один документ</a>?', array(
      '@url' => mcms_url(array('args' => array($this->getInstanceName() => array('status' => null)))),
      )) .'</p>';
    return array('html' => $output);
  }

  // Возвращает список типов, доступных пользователю.
  private function getTypeList($root = null)
  {
    $mode = $this->anonymous ? 'C' : 'c';

    // Типы, не привязанные к разделам.
    $all = "`n`.`id` NOT IN (SELECT `nid` FROM `node__rel` INNER JOIN `node` ON `node`.`id` = `node__rel`.`tid` WHERE `node`.`class` = 'tag')";

    // Типы, привязанные к текущему разделу.
    $current = "`n`.`id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` = ". intval($root) .")";

    // Запрос списка типов.
    $sql = "SELECT `v`.`name`, `t`.`title` FROM `node` `n` "
      ."INNER JOIN `node__rev` `v` ON `v`.`rid` = `n`.`rid` "
      ."INNER JOIN `node_type` `t` ON `t`.`rid` = `n`.`rid` "
      ."WHERE `n`.`id` IN (PERMCHECK:{$mode}) "
      ."AND `n`.`class` = 'type' "
      ."AND `n`.`deleted` = 0 "
      ."AND `t`.`hidden` = 0 "
      ."AND `t`.`internal` = 0 "
      ."AND `v`.`name` <> 'comment' "
      .(($this->showall or empty($root)) ? "" : "AND ({$all} OR {$current}) ")
      ."ORDER BY `t`.`title` "
      ;

    $types = mcms::db()->getResultsKV("name", "title", $sql);

    return $types;
  }

  // РАБОТА С ФОРМАМИ.

  public function formGet($id)
  {
    $form = null;
    $types = $this->getTypeList($this->options['root']);

    if ($id == 'form-create') {
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

      $node = Node::create($type);
      $form = $node->formGet($this->stripped);

      $form->addControl(new HiddenControl(array(
        'value' => 'referer',
        )));
    }

    return $form;
  }

  public function formGetData($id)
  {
    if ($id == 'form-create') {
      $data = array();
    }

    elseif (substr($id, 0, 12) == 'form-create-') {
      $node = Node::create(substr($id, 12), array(
        'parent_id' => $this->options['parent_id'],
        'uid' => mcms::user()->getUid(),
        ));
      $data = $node->formGetData();
    }

    $data['referer'] = empty($_SERVER['HTTP_REFERER']) ? null : $_SERVER['HTTP_REFERER'];

    return $data;
  }

  public function formProcess($id, array $data)
  {
    $next = null;

    switch ($id) {
    /*
    case 'form-create':
      $url = bebop_split_url();
      $url['args'][$this->getInstanceName()] = array(
        'type' => $data['node_content_class'],
        );

      $next = bebop_combine_url($url, false);
      break;
    */

    default:
      if (false !== strstr($id, 'form-create-')) {
        $type = substr($id, 12);

        $node = Node::create($type, array(
          'parent_id' => $this->options['parent_id'],
          'uid' => mcms::user()->getUid(),
          ));

        $node->formProcess($data);
        $node->save();

        mcms::flush();

        if (!empty($_GET['destination']))
          $next = $_GET['destination'];
        elseif (null !== $this->next)
          $next = $this->next;
        else {
          $next = mcms_url(array(
            'args' => array(
              $this->getInstanceName() => array(
                'status' => 'pending',
                ),
              ),
            ));
        }
      }
    }

    return $next;
  }
};
