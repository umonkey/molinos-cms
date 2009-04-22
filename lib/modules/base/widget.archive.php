<?php
/**
 * Виджет «навигация по архиву».
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Виджет «навигация по архиву».
 *
 * @package mod_base
 * @subpackage Widgets
 */
class ArchiveWidget extends Widget implements iWidget
{
  /**
   * Возвращает описание виджета для конструктора.
   *
   * @return array описание виджета, ключи: name, description.
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Архив документов',
      'description' => 'Позволяет фильтровать документы в разделе по дате, используя календарь. Работает только в паре со списком документов.',
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/ArchiveWidget',
      );
  }

  /**
   * Возвращает форму для настройки виджета.
   *
   * Добавляет к полученной от родиетля форме возможность выбрать базовый
   * виджет, с которым следует работать в паре.  Выбрать позволяют только один
   * из списков (ListWidget).
   *
   * @return Form вкладка формы, используется для настройки виджета.
   */
  public static function getConfigOptions(Context $ctx)
  {
    $widgets = array();

    foreach (Node::find($ctx->db, array('class' => 'widget')) as $w)
      if (!strcasecmp('ListWidget', $w->classname))
        $widgets[$w->name] = $w->title;

    return array(
      'host' => array(
        'type' => 'EnumControl',
        'label' => t('Привязать к виджету'),
        'description' => t('Выберите список документов, который будет параметризован этим виджетом.&nbsp; Ссылки, которые формирует навигатор по архиву, будут содержать параметры не для него самого, а для виджета, с которым он связан.'),
        'options' => $widgets,
        ),
      'reverse_years' => array(
        'type' => 'BoolControl',
        'label' => t('Список годов в обратном порядке'),
        ),
      'reverse_months' => array(
        'type' => 'BoolControl',
        'label' => t('Список месяцев в обратном порядке'),
        ),
      );
  }

  /**
   * Препроцессор параметров.
   *
   * Вытаскивает из текущего урла параметризацию виджета, в паре с которым
   * работает, в частности — параметры year, month, day.
   *
   * @return array параметры виджета.
   *
   * @param Context $ctx контекст вызова.
   */
  protected function getRequestOptions(Context $ctx, array $params)
  {
    if (is_array($options = parent::getRequestOptions($ctx, $params))) {
      // Нужно для подавления кэширования.
      $options['apath'] = $ctx->query();

      // Самостоятельно парсим урл, т.к. будем подглядывать за другими виджетами.
      // FIXME: это надо получать из контекста.
      $url = bebop_split_url();

      if (!empty($url['args'][$this->host])) {
        // Вытаскиваем нужные нам параметры.
        foreach (array('year', 'month', 'day') as $key) {
          // Первый же отсутствующий параметр прерывает цепочку.
          if (!array_key_exists($key, $url['args'][$this->host]))
            break;

          // Если параметр найден -- сохраняем его значение и продолжаем сканировать.
          $options[$key] = $url['args'][$this->host][$key];
        }
      }
    }

    // Возвращаем параметризацию.
    foreach (array('year', 'month', 'day') as $key) {
      if (array_key_exists($key, $options))
        $options['current'][$key] = $options[$key];
      elseif (array_key_exists($k = $this->host .'_'. $key, $_GET))
        $options['current'][$key] = $_GET[$k];
    }

    return $options;
  }

  /**
   * Обработчик GET запросов.
   *
   * @return mixed массив с данными для шаблона или NULL.
   *
   * @param array $options параметры, которые ранее насобирал метод
   * getRequestOptions().
   */
  public function onGet(array $options)
  {
    $result = array();

    // Находим родительский виджет.
    $host = Node::load(array(
      'class' => 'widget',
      'name' => $this->host,
      ));
    $conf = $host->config;

    // Вытаскиваем параметризацию родительского объекта.
    try {
      $tmp = Widget::getFor($this->host);
      $tmpoptions = $tmp->getRequestOptions($this->ctx);

      $query = $tmp->queryGet($tmpoptions);
      $tmpoptions['filter']['tags'] = $query['tags'];
    } catch (WidgetHaltedException $e) {
      return null;
    }

    // Достаём код раздела.
    if (!empty($tmpoptions['filter']['tags']))
      if (is_array($root = $tmpoptions['filter']['tags']))
        $root = array_shift($root);

    if (empty($root) and !empty($conf['fixed']))
      $root = $conf['fixed'];

    if (empty($root))
      $root = null;

    if (($options['root'] = $root) !== null) {
      try {
        $section = Node::load(array('class' => 'tag', 'id' => $options['root']));
        $result['section'] = $section->getRaw();
      } catch (ObjectNotFoundException $e) {
        return null;
      }

      // Здесь нам делать нечего.
      if (!empty($conf['onlyiflast']) and $conf['onlyiflast'] == 1 and !empty($options['next']))
        return array();

      // Возвращаем данные.
      $result['years'] = $this->getYearList($options, $query);
      if (!empty($options['year'])) {
        $result['months'] = $this->getMonthList($options, $query);
        if (!empty($options['month']))
          $result['days'] = $this->getDayList($options, $query);
      }

      $result['current'] = $options['current'];
    }

    return $result;
  }

  // Возвращаем массив годов.
  private function getYearList(array $options, array $query)
  {
    $result = array();

    $url = new url(array());
    $taglist = $this->getTagList($query);

    $sql = "SELECT YEAR(`created`) AS `year`, COUNT(*) AS `count` "
      ."FROM `node` WHERE `id` IN "
      ."(SELECT `nid` FROM `node__rel` WHERE `tid` IN ({$taglist})) "
      ."AND `published` = 1 "
      ."AND `created` IS NOT NULL "
      ."AND `deleted` = 0 "
      .$this->getQueryFilter($query);

    $sql .= "GROUP BY `year` ORDER BY `year`";

    if ($this->reverse_years)
      $sql .= ' DESC';

    // FIXME: publishing
    foreach ($this->ctx->db->getResultsKV("year", "count", $sql) as $k => $v) {
      if (!empty($k)) {
        $url->setarg($this->host .'.year', $k);
        $url->setarg($this->host .'.page', null);
        $result[$k] = $url->string();
      }
    }

    return $result;
  }

  private function getTagList(array $query)
  {
    if (empty($query['#recurse']))
      return sprintf("SELECT `nid` FROM `node__rel` "
        ."WHERE `tid` = %d", $query['tags'][0]);
    else
      return sprintf("SELECT a.id FROM node a, node b WHERE a.class = 'tag' "
        ."AND b.id = %d AND a.left >= b.left AND a.right <= b.right "
        ."AND a.deleted = 0 AND a.published = 1", $query['tags'][0]);
  }

  // Возвращает массив месяцев.
  private function getMonthList(array $options, array $query)
  {
    $result = array();
    $taglist = $this->getTagList($query);

    $url = bebop_split_url();
    $url['args'][$this->host]['day'] = null;

    $sql = "SELECT MONTH(`created`) AS `month`, "
      ."COUNT(*) AS `count` FROM `node` "
      ."WHERE `id` IN (SELECT `nid` FROM `node__rel` "
      ."WHERE `tid` IN ({$taglist})) AND YEAR(`created`) = :year "
      ."AND `published` = 1 "
      ."AND `deleted` = 0 "
      .$this->getQueryFilter($query)
      ."GROUP BY `month` ORDER BY `month`";

    if ($this->reverse_months)
      $sql .= ' DESC';

    // FIXME: publishing
    foreach ($this->ctx->db->getResultsKV("month", "count", $sql, array(':year' => $options['year'])) as $k => $v) {
      $url['args'][$this->host]['month'] = $k;
      $url['args'][$this->host]['page'] = null;
      $result[$k] = bebop_combine_url($url);
    }

    return $result;
  }

  // Возвращает массив дней.
  private function getDayList(array $options, array $query)
  {
    $result = '';
    $instance = $this->host;
    $taglist = $this->getTagList($query);

    $root = $options['root'];
    $year = $options['year'];
    $month = $options['month'];

    $url = bebop_split_url();

    $sql = "SELECT DAY(`n`.`created`) AS `day`, COUNT(*) AS `count` "
      ."FROM `node` `n` WHERE `n`.`id` IN "
      ."(SELECT `nid` FROM `node__rel` WHERE `tid` IN ({$taglist})) "
      ."AND YEAR(`n`.`created`) = :year AND MONTH(`n`.`created`) = :month "
      ."AND `n`.`published` = 1 "
      ."AND `n`.`deleted` = 0 "
      ."GROUP BY `day` ORDER BY `day`";

    // Список задействованных дней.
    // FIXME: publishing
    $days = $this->ctx->db->getResultsKV("day", "count", $sql, array(':year' => $year, ':month' => $month));

    // Список месяцев.
    $months = array('Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');

    // Получаем карту этого месяца.
    $cal = new Calendar();
    $map = $cal->getMonthView($month, $year);

    $result = "<table class='calendar' id='{$instance}-calendar'><tr>";

    // Предыдущий месяц.
    $tmp = $url;
    if (--$tmp['args'][$instance]['month'] == 0) {
      $tmp['args'][$instance]['year']--;
      $tmp['args'][$instance]['month'] = 12;
    }
    $result .= "<th class='prev'><a href='". bebop_combine_url($tmp) ."' title='{$months[$tmp['args'][$instance]['month'] - 1]}'><span>&larr;</span></a></th>";

    // Текущий месяц.
    $result .= "<th colspan='5' class='current'><span>{$months[$month - 1]}</span></th>";

    // Следующий месяц.
    $tmp = $url;
    if (++$tmp['args'][$instance]['month'] == 13) {
      $tmp['args'][$instance]['year']++;
      $tmp['args'][$instance]['month'] = 1;
    }
    $result .= "<th class='next'><a href='". bebop_combine_url($tmp) ."' title='{$months[$tmp['args'][$instance]['month'] - 1]}'><span>&rarr;</span></a></th></tr>";

    // Недели.
    foreach ($map as $week) {
      $result .= "<tr>";

      foreach ($week as $day) {
        $result .= "<td>";

        if (empty($day))
          $result .= "&nbsp;";
        elseif (empty($days[$day]))
          $result .= $day;
        else {
          $url['args'][$instance]['day'] = $day;
          $url['args'][$instance]['page'] = null;
          $result .= "<a href='". bebop_combine_url($url) ."'>{$day}</a>";
        }

        $result .= "</td>";
      }

      $result .= "</tr>";
    }

    $result .= "</table>";
    return $result;
  }

  private function getQueryFilter(array $query)
  {
    $sql = '';

    if (empty($query['class']))
      ;
    elseif (count($query['class']) == 1)
      $sql .= "AND `class` = '{$query['class'][0]}' ";
    else
      $sql .= "AND `class` IN ('". join("', '", $query['class']) ."') ";

    return $sql;
  }
};
