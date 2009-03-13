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
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'class' => __CLASS__,
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
    $query = $this->getQuery($options);
    $count = $query->getCount($this->ctx->db);

    if (0 == $count)
      return '<!-- nothing to show -->';

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
    $result .= Node::findXML($this->ctx->db, $query, null, null, 'document', 'documents');

    if ($this->pager)
      $result .= $this->getPager($count, $options['page'], $options['limit']);

    return $result;
  }

  private function getQuery(array $options)
  {
    $filter = array(
      'published' => 1,
      'deleted' => 0,
      '#sort' => $options['sort'],
      );

    if (!empty($options['classes']))
      $filter['class'] = $options['classes'];
    else
      $filter['class'] = null; // блокируем вывод

    if (!empty($options['section'])) {
      $filter['tags'] = $options['section'];
      if ($this->recurse)
        $filter['tags'] .= '+';
    }

    if (empty($filter['#sort']))
      $filter['#sort'] = '-id';

    if (!empty($options['limit'])) {
      $filter['#limit'] = $options['limit'];
      if (!empty($options['offset']))
        $filter['#offset'] = $options['offset'];
    }

    return new Query($filter);
  }
};
