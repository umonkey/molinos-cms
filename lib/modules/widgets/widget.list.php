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
  public static function getConfigOptions(Context $ctx)
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
        'group' => t('Типы выводимых документов'),
        'label' => t('Типы документов'),
        'options' => Node::getSortedList('type', 'title', 'name'),
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
  protected function getRequestOptions(Context $ctx, array $params)
  {
    $options = parent::getRequestOptions($ctx, $params);

    if ($this->onlyiflast and isset($params['document']))
      return $this->halt();

    if ('root' == $this->fixed)
      $options['section'] = $params['root'];
    elseif ('always' == $this->fallbackmode and $this->fixed)
      $options['section'] = array('id' => $this->fixed);
    elseif ($params['section'])
      $options['section'] = $params['section'];

    if (!empty($this->types))
      $options['classes'] = array_intersect((array)$this->types, $ctx->user->getAccess('r'));

    if ($this->onlyathome and $options['section'] != $params['root'])
      return $this->halt();

    if ($this->skipcurrent and isset($options['document']))
      $options['document'] = $options['document']['id'];

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

    // Добавляем пользовательскую фильтрацию.
    if ($tmp = $this->get('filter'))
      $options['filter'] = $tmp;

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
    $count = null;
    $result = html::wrap('nodes', Node::findXML($query = $this->getQuery($options), $this->ctx->db));

    // Пусто: откатываемся на другой раздел, но только если в текущем разделе
    // вообще ничего нет, на несуществующей странице выводим пустой список.
    if (empty($result)) {
      $count = $query->getCount($this->ctx->db);

      if (!$count and 'empty' == $this->fallbackmode and $this->fixed) {
        $options['section']['id'] = $this->fixed;
        $result = html::wrap('nodes', Node::findXML($query = $this->getQuery($options), $this->ctx->db));
        $count = null;
      }
    }

    if (!empty($result)) {
      // Добавляем информацию о разделе.
      if ($this->showpath and !empty($options['section'])) {
        $tmp = '';
        $section = Node::load($options['section'], $this->ctx->db);
        foreach ($section->getParents() as $node)
          $tmp .= $node->push('section');
        if (!empty($tmp))
          $result .= html::em('path', $tmp);
      }

      if ($this->pager and !empty($options['limit'])) {
        if (null === $count)
          $count = $query->getCount($this->ctx->db);
        $result .= $this->getPager($count, $options['page'], $options['limit']);
      }
    }

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
    elseif (isset($this->classes))
      $filter['class'] = explode(',', $this->classes);
    else
      throw new WidgetHaltedException(t('%name: не указаны типы документов.', array(
        '%name' => $this->name,
        )));

    if (isset($options['section']['id'])) {
      $filter['tags'] = $options['section']['id'];
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

    if (isset($options['filter']) and is_array($options['filter'])) {
      foreach ($options['filter'] as $k => $v) {
        if (!array_key_exists($k, $filter)) {
          if (!is_array($v))
            $v = explode(',', $v);
          $filter[$k] = $v;
        }
      }
    }

    return new Query($filter);
  }
};
