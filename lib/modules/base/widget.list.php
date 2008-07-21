<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:
// Возвращает список документов в разделе.  Поддерживает постраничную
// листалку и архив с привязкой к календарю.

class ListWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Список документов',
      'description' => 'Возвращает в переменную «$document» список документов для указанног раздела и — опционально — для всех подразделов.  Раздел, с которого начинается поиск документов, передаётся через адресную строку или задаётся жёстко, ниже.  Если раздел не указан, не возвращает ничего; ошибка 404 возникает только если указан несуществующий идентификатор раздела.',
      );
  }

  public static function formGetConfig()
  {
    $types = array();

    foreach (Node::find(array('class' => 'type')) as $type)
      // if (!in_array($type->name, TypeNode::getInternal()))
        $types[$type->id] = $type->title;

    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_fixed',
      'label' => t('Показывать документы из раздела'),
      'options' => TagNode::getTags('select'),
      'default' => t('Текущего (из пути или свойств страницы)'),
      'description' => t('В большинстве случаев нужен текущий раздел. Фиксированный используется только если список работает в отрыве от контекста запроса, например -- всегда показывает баннеры из фиксированного раздела.'),
      'required' => true,
      )));
    $form->addControl(new EnumControl(array(
      'value' => 'config_fallbackmode',
      'label' => t('Режим использования фиксированного раздела'),
      'options' => array(
        'always' => t('Всегда'),
        'empty' => t('Если в запрошенном ничего не найдено'),
        ),
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_recurse',
      'label' => t('Включить документы из подразделов'),
      'description' => t('Если этот флаг установлен, будут возвращены не только документы из запрошенного раздела, но и из всех его подразделов.'),
      )));
    $form->addControl(new TextLineControl(array(
      'value' => 'config_limit',
      'label' => t('Количество элементов на странице'),
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_onlyiflast',
      'label' => t('Возвращать список только если не запрошен документ'),
      'description' => t('Если этот флаг установлен, и в адресной строке после идентификатора раздела есть ещё какое-нибудь значение, виджет ничего не вернёт (при просмотре конкретного документа список обычно не нужен).'),
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_onlyathome',
      'label' => t('Возвращать список только на главной странице'),
      'description' => t('Если этот флаг установлен, список документов будет возвращён только если страница запрошена по своему основному адресу, без дополнительных параметров.&nbsp; Например, если виджет прикреплен к главной странице, а запрошена страница /xyz/, ничего возвращено не будет.'),
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_skipcurrent',
      'label' => t('Не возвращать текущий документ'),
      'description' => t('Исключить из списка документ, который уже отображается на странице.'),
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_pager',
      'label' => t('Использовать постраничную листалку'),
      'description' => t('Если эта опция выключена, массив $pager возвращаться не будет, и параметр .page=N обрабатываться не будет.'),
      )));
    $form->addControl(new TextLineControl(array(
      'value' => 'config_sort',
      'label' => t('Сортировка'),
      'description' => t('Правило сортировки описывается как список полей, разделённых пробелами. Обратная сортировка задаётся префиксом "-" перед именем поля.'),
      )));
    $form->addControl(new SetControl(array(
      'value' => 'config_types',
      'label' => t('Типы документов'),
      'options' => $types,
      )));

    return $form;
  }

  public function formHookConfigData(array &$data)
  {
    $data['config_types'] = $this->me->linkListParents('type', true);
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['picker'] = $ctx->get('picker');
    $options['limit'] = $ctx->get('limit', $this->limit);

    if (!is_array($options['filter'] = $ctx->get('filter')))
      $options['filter'] = array();

    if (null !== ($tmp = $ctx->get('special')))
      $options['filter']['#special'] = $tmp;
    else {
      if (null !== ($tmp = $ctx->get('search')))
        $options['filter']['#search'] = $tmp;

      // Выбор текущего раздела.  Если сказано всегда использовать
      // фиксированный -- используем его, в противном случае
      // используем текущий раздел в зависимости от запроса и
      // настроек текущей страницы.

      if ('always' == $this->fallbackmode and !empty($this->fixed))
        $options['filter']['tags'] = array($this->fixed);
      elseif (null !== ($tmp = $ctx->section_id))
        $options['filter']['tags'] = array($ctx->section_id);

      if ($this->skipcurrent)
        $options['current_document'] = $ctx->document_id;

      if (is_array($tmp = $ctx->get('classes')))
        $options['filter']['class'] = array_unique($tmp);

      // Добавляем выборку по архиву.
      foreach (array('year', 'month', 'day') as $key) {
        if (null === ($tmp = $ctx->get($key)))
          break;
        $options['filter']['node.created.'. $key] = intval($tmp);
      }
    }

    if ($options['limit'] = $ctx->get('limit', $this->limit)) {
      if ($this->pager)
        $options['page'] = $ctx->get('page', 1);
      else
        $options['page'] = 1;

      $options['offset'] = ($options['page'] - 1) * $options['limit'];
    } else {
      $options['offset'] = null;
    }

    // Определяем сортировку.

    if (is_array($tmp = $ctx->get('sort')))
      $options['sort'] = $tmp;

    elseif (empty($this->sort))
      ;

    elseif (!is_array($this->sort)) {
      $fields = explode(' ', $this->sort);

      foreach (explode(' ', $this->sort) as $field) {
        // Это происходит если пользователь ввёл больше одного пробела.
        if (empty($field))
          continue;

        $dir = 'ASC';

        switch (substr($field, 0, 1)) {
        case '-':
          $dir = 'DESC';
        case '+':
          $field = substr($field, 1);
          break;
        }

        $options['sort'][$field] = $dir;
      }
    }

    elseif (!empty($this->sort['fields'])) {
      foreach ($this->sort['fields'] as $field) {
        $reverse = empty($this->sort['reverse']) ? array() : $this->sort['reverse'];
        $options['sort'][$field] = in_array($field, $reverse) ? 'DESC' : 'ASC';
      }
    }

    if (!empty($options['sort']) and array_key_exists('RAND()', $options['sort']))
      $options['#nocache'] = true;

    return $this->options = $options;
  }

  protected function onGetList(array $options)
  {
    if (($filter = $this->queryGet()) === null)
      return null;

    if (empty($filter['tags']))
      return null;

    if ($this->getInstanceName() == 'tracker')
      unset($filter['tags']);

    $result = array(
      'path' => array(),
      'section' => array(),
      'documents' => array(),
      'schema' => array(),
      );

    // Возращаем путь к текущему корню.
    // FIXME: это неверно, т.к. виджет может возвращать произвольный раздел!
    if (null !== $this->ctx->section_id) {
      foreach ($this->ctx->section->getParents() as $node)
        $result['path'][] = $node->getRaw();
    }

    if (empty($this->options['filter']['tags']))
      $result['section'] = null;
    else {
      $node = Node::load(array(
        'class' => 'tag',
        'id' => $this->options['filter']['tags'][0],
        ));
      $result['section'] = $node->getRaw();
    }

    // Возвращаем список разделов.
    // $result['sections'] = empty($filter['tags']) ? array() : $filter['tags'];

    // Добавляем пэйджер.
    if (!empty($options['limit'])) {
      if ($this->pager and empty($filter['#sort']['RAND()'])) {
        $options['count'] = Node::count($filter);

        $result['pager'] = $this->getPager($options['count'], $options['page'], $options['limit']);
        if ($result['pager']['pages'] < 2)
          unset($result['pager']);
      }
    }

    // Формируем список документов.
    foreach ($nodes = Node::find($filter, $options['limit'], $options['offset']) as $node) {
      $result['documents'][] = $node->getRaw();
      $result['keys'][] = $node->id;

      if (!array_key_exists($node->class, $result['schema']))
        $result['schema'][$node->class] = TypeNode::getSchema($node->class);
    }

    // Добавляем информацию о поиске.
    if (!empty($options['search'])) {
      $result['search'] = array(
        'string' => $options['search'],
        'reset' => l(null, array($this->getInstanceName() => array('search' => null))),
        );
    }

    $result['options'] = $options;
    $result['root'] = $this->ctx->section;

    return $result;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    try {
      $options['mode'] = 'list';
      return $this->dispatch(array($options['mode']), $options);
    } catch (NoIndexException $e) {
      return array('error' => t('Не удалось получить список документов: отсутствуют индексы.'));
    }
  }

  public function onPost(array $options, array $post, array $files)
  {
    if (!empty($post['action'])) {
      switch ($post['action']) {
      case 'search':
        $url = bebop_split_url();
        $url['args'][$this->getInstanceName()] = array(
          'search' => empty($post['search']) ? null : $post['search'],
          );

        mcms::redirect($url);

        return bebop_combine_url($url, false);
      }
    }
  }

  private function getNodePerms(array $nodes, $op)
  {
    return mcms::db()->getResultsKV("nid", "nid", "SELECT `nid` FROM `node__access` WHERE `nid` IN ({$op}CHECK) AND `nid` IN (". join(", ", $nodes) .")");
  }

  private function getTagList($root, $recurse)
  {
    if (empty($root))
      return array();

    if (!$recurse)
      return array($root);

    $tags = mcms::db()->getResultsV("id", "SELECT `n`.`id` as `id` FROM `node` `n`, `node` `t` "
      ."WHERE `t`.`id` = :tid AND `n`.`left` >= `t`.`left` AND `n`.`right` <= `t`.`right` "
      ."AND `n`.`deleted` = 0 AND `n`.`published` = 1 "
      ."ORDER BY `n`.`left` -- ListWidget::getTagList()", array(':tid' => $root));

    return $tags;
  }

  // Возвращает таблицу с параметрами сортировки.
  private function getSortTable()
  {
    $result = array(
      '#type' => 'TableControl',
      '#text' => 'Сортировка документов',
      '#description' => "Сортировать по другим полям пока нельзя.",
      '#header' => array(null, 'Название поля', 'Обратная'),
      '#rows' => array(
        array(),
        ),
      );

    foreach (array('id' => 'Код документа', 'name' => 'Название', 'created' => 'Дата создания', 'updated' => 'Дата изменения', 'RAND()' => 'В случайном порядке') as $field => $title) {
      $result['#rows'][] = array(
        array(
          '#type' => 'BoolControl',
          '#name' => 'config[sort][fields][]',
          '#value' => $field,
          '#checked' => empty($this->config['sort']['fields']) ? false : in_array($field, $this->config['sort']['fields']),
          ),
        $title,
        array(
          '#type' => 'BoolControl',
          '#name' => 'config[sort][reverse][]',
          '#value' => $field,
          '#checked' => empty($this->config['sort']['reverse']) ? false : in_array($field, $this->config['sort']['reverse']),
          '#disabled' => ($field == 'RAND()'),
          ),
        );
    }

    return $result;
  }

  // Возвращает объект NodeQueryBuilder для получения данных.
  protected function queryGet(array $options = null)
  {
    $query = array();

    if (null === $options)
      $options = $this->options;

    if (!empty($this->options['special']))
      $query['#special'] = $options['special'];

    else {
      if (!empty($options['filter']))
        foreach ($options['filter'] as $k => $v)
          if ('' !== $v)
            $query[$k] = $v;

      if (!empty($options['classes']) and is_array($options['classes']))
        $query['class'] = $options['classes'];
      elseif (empty($query['class']) and empty($query['-class']))
        foreach (Node::find(array('class' => 'type', 'id' => $this->me->linkListParents('type', true))) as $type)
          $query['class'][] = $type->name;

      if (!array_key_exists('published', $query))
        $query['published'] = 1;

      if (!empty($options['search']))
        $query['#search'] = $options['search'];

      if ($this->skipcurrent and null !== $this->ctx->document_id)
        $query['-id'] = $this->ctx->document_id;

      if (!empty($query['tags'])) {
        if (!is_array($query['tags']))
          $query['tags'] = $this->queryGetTags($query['tags']);
        elseif (count($query['tags']) == 1)
          $query['tags'] = $this->queryGetTags($query['tags'][0]);
      }

      // Переключаемся на дефолтный раздел, если это нужно.
      if (!empty($this->fixed) and 'empty' == $this->fallbackmode) {
        if (empty($query['tags']) or !($count = Node::count($query)))
          $query['tags'] = $this->queryGetTags($this->fixed);
      }
    }

    if (!empty($options['sort'])) {
      $query['#sort'] = $options['sort'];
    } else {
      // Этого здесь НЕ должно быть, иначе в админке нельзя будет сортировать документы в рамках раздела.
      // $query['#sort'] = array('id' => 'desc');
    }

    $query['#permcheck'] = true;
    $query['#recurse'] = 1;

    return $query;
  }

  private function queryGetTags($root)
  {
    if (empty($root))
      return array();

    if (!$this->recurse)
      return array($root);

    $field = is_numeric($root) ? 'id' : 'code';

    $tags = mcms::db()->getResultsV("id", "SELECT `n`.`id` as `id` FROM `node` `n`, `node` `t` "
      ."WHERE `t`.`{$field}` = :root AND `n`.`left` >= `t`.`left` AND `n`.`right` <= `t`.`right` "
      ."AND `n`.`deleted` = 0 AND `n`.`published` = 1 "
      ."ORDER BY `n`.`left` -- ListWidget::getTagList()", array(':root' => $root));

    return $tags;
  }
};
