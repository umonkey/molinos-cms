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
    $query = $this->getQuery($options, $options['section']);
    $count = $query->getCount($this->ctx->db);

    if (0 == $count) {
      if ($this->fallbackmode != 'empty' or !$this->fixed)
        return '<!-- nothing to show -->';

      $query = $this->getQuery($options, $this->fixed);

      if (!($count = $query->getCount($this->ctx->db)))
        return '<!-- nothing to show, even in the fixed section -->';
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
    $result .= Node::findXML($this->ctx->db, $query, null, null, 'document', 'documents');

    if ($this->pager)
      $result .= $this->getPager($count, $options['page'], $options['limit']);

    return $result;
  }

  private function getQuery(array $options, $section_id)
  {
    $filter = array(
      'published' => 1,
      'deleted' => 0,
      '#sort' => $options['sort'],
      );

    if (!empty($options['classes']))
      $filter['class'] = $options['classes'];

    if (!empty($section_id)) {
      $filter['tags'] = $section_id;
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
