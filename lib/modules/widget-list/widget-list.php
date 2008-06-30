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

    foreach (Node::find(array('class' => 'type', 'internal' => 0, '#sort' => array('type.title' => 'asc'))) as $type)
      $types[$type->id] = $type->title;

    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_fixed',
      'label' => t('Раздел по умолчанию'),
      'options' => TagNode::getTags('select'),
      'default' => t('не использовать'),
      )));
    $form->addControl(new EnumControl(array(
      'value' => 'config_fallbackmode',
      'label' => t('Режим использования фиксированного раздела'),
      'options' => array(
        'empty' => 'Если в запрошенном ничего не найдено',
        'notset' => 'Если конкретный раздел не запрошен',
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
    $form->addControl(new SetControl(array(
      'value' => 'config_types',
      'label' => t('Типы документов'),
      'options' => $types,
      )));
    $form->addControl(new TextLineControl(array(
      'value' => 'config_sort',
      'label' => t('Сортировка'),
      'description' => t('Укажите поля, по которым нужно сортировать список.  Обратная сортировка задаётся минусом, например: -product.size id.'),
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

    if ('download' == ($options['mode'] = $ctx->get('mode', 'list')))
      $options['format'] = $ctx->get('format', 'xml');

    if (!is_array($options['filter'] = $ctx->get('filter')))
      $options['filter'] = array();

    if (null !== ($tmp = $ctx->get('special')))
      $options['filter']['#special'] = $tmp;
    else {
      if (null !== ($tmp = $ctx->get('search')))
        $options['filter']['#search'] = $tmp;

      if (null !== ($ctx->section_id))
        $options['filter']['tags'] = array($ctx->section->id);
      elseif (null !== ($tmp = $ctx->get('section')))
        $options['filter']['tags'] = array($tmp);
      elseif ('empty' != $this->fallbackmode and !empty($this->fixed))
        $options['filter']['tags'] = array($this->fixed);

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
      $options['page'] = $ctx->get('page', 1);
      $options['offset'] = ($options['page'] - 1) * $options['limit'];
    } else {
      $options['offset'] = null;
    }

    // Определяем сортировку.

    if (is_array($tmp = $ctx->get('sort')))
      $options['sort'] = $tmp;

    elseif (!empty($this->sort) and is_string($this->sort)) {
      foreach (explode(' ', $this->sort) as $key) {
        $key = trim($key);

        if (!empty($key)) {
          $dir = 'asc';

          if (substr($key, 0, 1) == '-') {
            $dir = 'desc';
            $key = substr($key, 1);
          }

          $options['sort'][$key] = $dir;
        }
      }
    }

    elseif (!empty($this->sort['fields']) and is_array($this->sort['fields'])) {
      foreach ($this->sort['fields'] as $field) {
        $reverse = empty($this->sort['reverse']) ? array() : $this->sort['reverse'];
        $options['sort'][$field] = in_array($field, $reverse) ? 'DESC' : 'ASC';
      }
    }

    if (!empty($options['sort']) and array_key_exists('RAND()', $options['sort']))
      $options['#nocache'] = true;

    return $this->options = $options;
  }

  protected function onGetDownload(array $options)
  {
    $output = '';

    $nodes = Node::find($filter = $this->queryGet());
    $schema = TypeNode::getSchema();

    switch ($options['format']) {
    case 'xml':
      $output .= '<?xml version="1.0"?>';
      $output .= '<data>';

      foreach ($nodes as $node) {
        $output .= "<node id='{$node->id}' revision='{$node->rid}' type='{$node->class}' classname='". mcms_plain($schema[$node->class]['title']) ."' uid='{$node->uid}' created='{$node->created}' updated='{$node->updated}'>";

        if (!empty($schema[$node->class]['fields']) and is_array($schema[$node->class]['fields'])) {
          $fields = $schema[$node->class]['fields'];

          foreach ($fields as $k => $v) {
            if ($node->$k !== null and $node->$k !== '') {
              $value = is_array($node->$k) ? serialize($node->$k) : $node->$k;
              $output .= "<field name='{$k}' title='". mcms_plain($v['label']) ."'>". mcms_plain($value) ."</field>";
            }
          }
        }

        $output .= "</node>";
      }

      $output .= '</data>';

      header('Content-Type: text/xml; charset=utf-8');
      break;

    default:
      throw new PageNotFoundException();
    }

    header('Content-Length: '. strlen($output));
    die($output);
  }

  protected function onGetList(array $options)
  {
    if (($filter = $this->queryGet()) === null)
      return null;

    if (empty($filter['tags']))
      return null;

    $result = array(
      'path' => array(),
      'sections' => array(),
      'documents' => array(),
      'schema' => array(),
      );

    // Возращаем путь к текущему корню.
    // FIXME: это неверно, т.к. виджет может возвращать произвольный раздел!
    if (null !== $this->ctx->section_id)
      $result['path'] = array_values($this->ctx->section->getParents());

    // Возвращаем список разделов.
    $result['sections'] = empty($filter['tags']) ? array() : $filter['tags'];

    // Добавляем пэйджер.
    if (!empty($options['limit'])) {
      if (empty($filter['#sort']['RAND()'])) {
        $options['count'] = Node::count($filter);

        $result['pager'] = $this->getPager($options['count'], $options['page'], $options['limit']);
        if ($result['pager']['pages'] < 2)
          unset($result['pager']);
      }
    }

    // Формируем список документов.
    foreach (Node::find($filter, $options['limit'], $options['offset']) as $node) {
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

    return $result;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['mode']), $options);
  }

  public function onPost(array $options, array $post, array $files)
  {
    switch (@$post['action']) {
    case 'delete':
      if (!empty($post['nodes'])) {
        foreach (Node::find(array('id' => (array)$post['nodes'])) as $node)
          $node->delete();
        BebopCache::getInstance()->flush();
      }
      break;
    }
  }

  private function getNodePerms(array $nodes, $op)
  {
    return PDO_Singleton::getInstance()->getResultsKV("nid", "nid", "SELECT `nid` FROM `node__access` WHERE `nid` IN ({$op}CHECK) AND `nid` IN (". join(", ", $nodes) .")");
  }

  private function getTagList($root, $recurse)
  {
    if (empty($root))
      return array();

    if (!$recurse)
      return array($root);

    $tags = PDO_Singleton::getInstance()->getResultsV("id", "SELECT `n`.`id` FROM `node` `n`, `node` `t` "
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

      if (!empty($query['tags']) and !is_array($query['tags']))
        $query['tags'] = $this->queryGetTags($query['tags']);

      // Переключаемся на дефолтный раздел, если это нужно.
      if (!empty($this->fixed) and 'empty' == $this->fallbackmode) {
        if (empty($query['tags']) or !($count = Node::count($query)))
          $query['tags'] = $this->queryGetTags($this->fixed);
      }
    }

    if (!empty($options['sort']))
      $query['#sort'] = $options['sort'];

    return $query;
  }

  private function queryGetTags($root)
  {
    if (empty($root))
      return array();

    if (!$this->recurse)
      return array(parent::getRealNodeId($root));

    $field = is_numeric($root) ? 'id' : 'code';

    $tags = PDO_Singleton::getInstance()->getResultsV("id", "SELECT `n`.`id` FROM `node` `n`, `node` `t` "
      ."WHERE `t`.`{$field}` = :root AND `n`.`left` >= `t`.`left` AND `n`.`right` <= `t`.`right` "
      ."AND `n`.`deleted` = 0 AND `n`.`published` = 1 "
      ."ORDER BY `n`.`left` -- ListWidget::getTagList()", array(':root' => $root));

    return $tags;
  }
};
