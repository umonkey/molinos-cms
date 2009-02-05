<?php
/**
 * Виджет «список документов».
 *
 * Возвращает список объектов, соответствующих условию.  Поддерживает
 * постраничную навигацию.
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Виджет «список документов».
 *
 * Возвращает список объектов, соответствующих условию.  Поддерживает
 * постраничную навигацию.
 *
 * @package mod_base
 * @subpackage Widgets
 */
class ListWidget extends Widget
{
  /**
   * Возвращает описание виджета.
   *
   * @return array массив с описанием виджета, ключи: name, description.
   */
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Список документов',
      'description' => 'Позволяет выбирать документы из разделов и сортировать их.',
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/ListWidget',
      );
  }

  /**
   * Возвращает форму для настройки виджета.
   *
   * @return Form вкладка с настройками виджета.
   */
  public static function getConfigOptions()
  {
    $schema = array(
      'fixed' => array(
        'type' => 'SectionControl',
        'label' => t('Показывать документы из раздела'),
        'prepend' => array(
          'root' => t('Основного для страницы (или домена)'),
          ),
        'description' => t('В большинстве случаев нужен текущий раздел. Фиксированный используется только если список работает в отрыве от контекста запроса, например -- всегда показывает баннеры из фиксированного раздела.'),
        'required' => false,
        'default_label' => t('Текущего (из пути или свойств страницы)'),
        'store' => true,
        ),
      'fallbackmode' => array(
        'type' => 'EnumControl',
        'label' => t('Режим использования фиксированного раздела'),
        'options' => array(
          'always' => t('Всегда'),
          'empty' => t('Если в запрошенном ничего не найдено'),
          ),
        ),
      'recurse' => array(
        'type' => 'BoolControl',
        'label' => t('Включить документы из подразделов'),
        ),
      'showpath' => array(
        'type' => 'BoolControl',
        'label' => t('Возвращать информацию о разделе'),
        'description' => t('Снижает производительность.'),
        ),
      'limit' => array(
        'type' => 'NumberControl',
        'label' => t('Количество элементов на странице'),
        ),
      'onlyiflast' => array(
        'type' => 'BoolControl',
        'label' => t('Возвращать список только если не запрошен документ'),
        ),
      'onlyathome' => array(
        'type' => 'BoolControl',
        'label' => t('Возвращать список только если не запрошен конкретный раздел'),
        ),
      'skipcurrent' => array(
        'type' => 'BoolControl',
        'label' => t('Не возвращать текущий документ'),
        ),
      'count_comments' => array(
        'type' => 'BoolControl',
        'label' => t('Возвращать количество комментариев'),
        'ifmodule' => 'comment',
        ),
      'pager' => array(
        'type' => 'BoolControl',
        'label' => t('Использовать постраничную листалку'),
        'description' => t('Если эта опция выключена, массив $pager возвращаться не будет, и параметр .page=N обрабатываться не будет.'),
        ),
      'allowoverride' => array(
        'type' => 'BoolControl',
        'label' => t('Разрешить переопределять раздел'),
        'description' => t('При включении можно будет использовать ?виджет.section=123 для изменения раздела, с которым работает виджет.'),
        ),
      'sort' => array(
        'type' => 'TextLineControl',
        'label' => t('Сортировка'),
        'description' => t('Правило сортировки описывается как список полей, разделённых пробелами. Обратная сортировка задаётся префиксом "-" перед именем поля.'),
        ),
      'types' => array(
        'type' => 'SetControl',
        'label' => t('Возвращать документы следующих типов'),
        'dictionary' => 'type',
        'field' => 'title',
        'parents' => true,
        'group' => t('Доступ'),
        ),
      );

    return $schema;
  }

  /**
   * Препроцессор параметров.
   *
   * Выбирает из информации о контексте параметры, относящиеся к этому виджету.
   *
   * @param Context $ctx контекст запроса.
   *
   * @return array массив с параметрами.
   */
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    if ($this->onlyiflast and $ctx->document->id)
      return false;

    // Выбор текущего раздела.
    if ($this->allowoverride and ($tmp = $this->get('section')))
      $options['section'] = $tmp;
    elseif ('root' == $this->fixed)
      $options['section'] = $ctx->root->id;
    elseif ('always' == $this->fallbackmode and $this->fixed)
      $options['section'] = $this->fixed;
    elseif (null !== ($tmp = $ctx->section->id))
      $options['section'] = $tmp;

    if (!empty($this->types))
      $options['classes'] = array_intersect($this->types, Context::last()->user->getAccess('r'));

    if ($this->onlyathome and $options['section'] != $ctx->root->id)
      return false;

    if ($this->skipcurrent)
      $options['document'] = $ctx->document->id;

    if (is_array($tmp = $this->get('classes')))
      $options['filter']['class'] = array_unique($tmp);

    // Добавляем выборку по архиву.
    foreach (array('year', 'month', 'day') as $key) {
      if (null === ($tmp = $this->get($key)))
        break;
      $options['filter']['node.created.'. $key] = $tmp;
    }

    // Добавляем выбор страницы.
    if ($options['limit'] = $this->get('limit', $this->limit)) {
      if ($this->pager)
        $options['page'] = $this->get('page', 1);
      else
        $options['page'] = 1;

      $options['offset'] = ($options['page'] - 1) * $options['limit'];
    } else {
      $options['offset'] = null;
    }

    $options['sort'] = $this->get('sort', $this->sort);

    return $options;
  }

  /**
   * Формирование списка документов.
   *
   * Основной критерий отбора документов — привязка к определённому разделу.
   *
   * @param array $options параметры запроса.
   *
   * @return array результат работы, ключи:
   *
   * path — полный путь к текущему разделу, от корневого до текущего.  Содержит
   * полные описания объектов.
   *
   * section — описание первого раздела, к которому привязан список.
   * FIXME: оставить либо это, либо root.
   *
   * pager — данные для построения постраничной навигации, см.
   * Widget::getPager().
   *
   * documents — массив описаний документов
   *
   * root — описание текущего раздела.
   *
   * schema — массив структур.  Содержит только те типы, которые использованы в
   * documents.
   *
   * options — параметры, которые отобрал getRequestOptions().  Могут
   * использоваться для вывода информации о документах.
   */
  protected function onGetList(array $options)
  {
    if (($filter = $this->queryGet($options)) === null)
      return "<!-- widget {$this->name} halted: no query. -->";

    if (empty($filter['tags']))
      return "<!-- widget {$this->name} halted: no tags. -->";

    $output = '';

    $result = array(
      'path' => array(),
      'section' => array(),
      'documents' => array(),
      'schema' => array(),
      'document' => $options['document'],
      );

    // Возращаем путь к текущему корню.
    // FIXME: это неверно, т.к. виджет может возвращать произвольный раздел!
    if (null !== $this->ctx->section) {
      $tmp = '';
      foreach ($this->ctx->section->getParents() as $node) {
        $tmp .= $node->push('section');
      }
      $output .= html::em('path', $tmp);
    }

    if (empty($options['filter']['tags']))
      $result['section'] = null;
    else {
      $node = NodeStub::create($options['filter']['tags'][0], $this->ctx->db);
      $output .= $node->push('section');
    }

    // Получаем список документов.
    $nodes = Node::find($filter, $options['limit'], $options['offset']);
    $this->countComments($nodes);

    // Формируем список документов.
    $tmp = '';
    foreach ($nodes as $node)
      $tmp .= $node->push('document');
    if (!empty($tmp))
      $output .= html::em('documents', $tmp);

    // Добавляем пэйджер.
    if (!empty($options['limit'])) {
      if ($this->pager and empty($filter['#sort']['RAND()'])) {
        $options['count'] = Node::count($filter);

        $output .= mcms::pager($options['count'], $options['page'],
          $options['limit'], $this->getInstanceName() .'.page');
      }
    }

    /*
    // Добавляем информацию о поиске.
    if (!empty($options['search'])) {
      $result['search'] = array(
        'string' => $options['search'],
        'reset' => l(null, array($this->getInstanceName() => array('search' => null))),
        );
    }
    */

    // $this->countComments($result);

    // $result['options'] = $options;

    return $output;
  }

  private function countComments(array &$result)
  {
    if ($this->count_comments) {
      $ids = array_keys($result);
      $data = $this->ctx->db->getResultsKV("id", "cnt", "SELECT r.tid AS id, COUNT(*) AS cnt FROM node__rel r INNER JOIN node n ON n.id = r.nid WHERE n.class = 'comment' AND n.published = 1 AND n.deleted = 0 AND r.tid IN (". join(', ', $ids) .") GROUP BY r.tid");

      foreach ($data as $k => $v)
        $result[$k]->commentCount = $v;
    }
  }

  /**
   * Диспетчер запросов.
   *
   * В зависимости от GET-параметра mode вызывает один из методов: onGetList()
   * или больше никакой.  Гасит ошибки NoIndexException, возвращая вместо них
   * массив с ключём "error", значение которого содержит описание ошибки.
   *
   * @param array $options то, что насобирал getRequestOptions().
   *
   * @return mixed то, что вернул конкретный метод-обработчик.
   */
  public function onGet(array $options)
  {
    if (!count($ids = $this->getDocumentIds($options))) {
      // Обработать fallback.
    }

    $result = '';

    // Добавляем информацию о разделе.
    if ($this->showpath and !empty($options['section'])) {
      $tmp = '';
      $section = NodeStub::create($options['section'], $this->ctx->db);
      foreach ($section->getParents() as $node)
        $tmp .= $node->push('section');
      if (!empty($tmp))
        $result .= html::em('path', $tmp);
    }

    // Формируем список документов.
    $tmp = '';
    foreach ($ids as $id) {
      $node = NodeStub::create($id, $this->ctx->db);
      $tmp .= $node->getXML('document');
    }
    if (!empty($tmp))
      $result .= html::em('documents', $tmp);

    return $result;
  }

  private function getDocumentIds(array $options)
  {
    $tables = array('`node`');
    $where = $params = array();

    $this->getBasicQuery($options, $where, $params);

    if (!empty($options['sort']))
      $order = $this->getSortQuery($options['sort'], $tables, $where, $params);
    else
      $order = null;

    $sql = "SELECT `id` FROM `node` WHERE " . join(' AND ', $where);
    $sql .= $order;

    if (!empty($options['limit'])) {
      $lim = intval($options['limit']);
      $off = empty($options['page'])
        ? 0
        : ($options['page'] - 1) * $lim;
      $sql .= " LIMIT {$off}, {$lim}";
    }

    return (array)$this->ctx->db->getResultsV('id', $sql, $params);
  }

  private function getBasicQuery(array $options, array &$where, array &$params)
  {
    $where[] = "`node`.`published` = 1";
    $where[] = "`node`.`deleted` = 0";

    if (!empty($options['classes']))
      $where[] = "`node`.`class` " . $this->getIn($options['classes'], $params);

    if (!empty($options['section'])) {
      if ($this->recurse) {
        $where[] = "`node`.`id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` IN (SELECT n1.id FROM node n1, node n2 WHERE n1.left >= n2.left AND n1.right <= n2.right AND n1.class = 'tag' AND n1.deleted = 0 AND n1.published = 1 AND n2.id = ?))";
        $params[] = $options['section'];
      } else {
        $in = $this->getIn($options['section'], $params);
        $where[] = "`node`.`id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` {$in})";
      }
    }
  }

  private function getSortQuery($order, array &$tables, array &$where, array &$params)
  {
    $parts = array();
    $order = preg_split('@[, ]+@', $order, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($order as $part) {
      if ('-' == substr($part, 0, 1)) {
        $mode = 'DESC';
        $part = substr($part, 1);
      } else {
        $mode = 'ASC';
      }

      if (1 == count($fspec = explode('.', $part, 2))) {
        $table = '`node`';
        $field = $fspec[0];
      } else {
        $table = '`node__idx_' . $fspec[0] . '`';
        $field = $fspec[1];
      }

      if (!in_array($table, $tables)) {
        $tables[] = $table;
        $where[] = $table . '.`id` = `node`.`id`';
      }

      $parts[] = $table . '.`' . $field . '` ' . $mode;
    }

    return empty($parts)
      ? null
      : ' ORDER BY ' . join(', ', $parts);
  }

  /**
   * Возвращает SQL инструкцию для выборки по списку значений.
   * Варианты: IS NULL, = ?, IN (?..).
   */
  private function getIn($values, array &$params)
  {
    if (empty($values))
      return 'IS NULL';

    if (!is_array($values))
      $values = array($values);

    if (1 == count($values)) {
      $params[] = array_shift($values);
      return ' = ?';
    }

    $qs = array();
    foreach ($values as $v) {
      $qs[] = '?';
      $params[] = $v;
    }

    return 'IN (' . join(', ', $qs) . ')';
  }
};
