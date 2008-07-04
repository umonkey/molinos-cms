<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ArchiveWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Архив документов',
      'description' => 'Позволяет фильтровать документы в разделе по дате, используя календарь.&nbsp; Сам по себе виджет не интересен, работает только в тандеме со списками документов.',
      );
  }

  public static function formGetConfig()
  {
    $widgets = array();

    foreach (Node::find(array('class' => 'widget')) as $w) {
      if ('ListWidget' != $w->classname)
        continue;
      if (substr($w->name, 0, 5) == 'Bebop')
        continue;
      $widgets[$w->name] = $w->title;
    }

    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_host',
      'label' => t('Привязать к виджету'),
      'description' => t('Выберите список документов, который будет параметризован этим виджетом.&nbsp; Ссылки, которые формирует навигатор по архиву, будут содержать параметры не для него самого, а для виджета, с которым он связан.'),
      'options' => $widgets,
      )));

    return $form;
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    // Нужно для подавления кэширования.
    $options['apath'] = $ctx->apath;

    // Самостоятельно парсим урл, т.к. будем подглядывать за другими виджетами.
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

    return $this->options = $options;
  }

  // Обработка GET запросов.
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
      $tmp = new ListWidget($host);
      $tmpoptions = $tmp->getRequestOptions($this->ctx);
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
      $result['years'] = $this->getYearList($options);
      if (!empty($options['year'])) {
        $result['months'] = $this->getMonthList($options);
        if (!empty($options['month']))
          $result['days'] = $this->getDayList($options);
      }

      // mcms::debug($options, $result);

      // Возвращаем параметризацию.
      foreach (array('year', 'month', 'day') as $key)
        if (array_key_exists($key, $options))
          $result['current'][$key] = $options[$key];
    }

    return $result;
  }

  // Возвращаем массив годов.
  private function getYearList(array $options)
  {
    $result = array();

    $url = bebop_split_url();

    $url['args'][$this->host]['month'] = null;
    $url['args'][$this->host]['day'] = null;
    $url['path'] = $this->getUrlPath();

    $sql = "SELECT YEAR(`created`) AS `year`, COUNT(*) AS `count` "
      ."FROM `node` WHERE `id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` = :tid) "
      ."AND `published` = 1 GROUP BY `year` ORDER BY `year`";

    // FIXME: publishing
    foreach (mcms::db()->getResultsKV("year", "count", $sql, array(':tid' => $options['root'])) as $k => $v) {
      $url['args'][$this->host]['year'] = $k;
      $result[$k] = bebop_combine_url($url);
    }

    return $result;
  }

  // Возвращает массив месяцев.
  private function getMonthList(array $options)
  {
    $result = array();

    $url = bebop_split_url();
    $url['args'][$this->host]['day'] = null;
    $url['path'] = $this->getUrlPath();

    // FIXME: publishing
    foreach (mcms::db()->getResultsKV("month", "count", "SELECT MONTH(`created`) AS `month`, COUNT(*) AS `count` FROM `node` WHERE `id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` = :tid) AND YEAR(`created`) = :year AND `published` = 1 GROUP BY `month` ORDER BY `month`", array(':tid' => $options['root'], ':year' => $options['year'])) as $k => $v) {
      $url['args'][$this->host]['month'] = $k;
      $result[$k] = bebop_combine_url($url);
    }

    return $result;
  }

  // Возвращает массив дней.
  private function getDayList(array $options)
  {
    $result = '';
    $instance = $this->host;

    $root = $options['root'];
    $year = $options['year'];
    $month = $options['month'];

    $url = bebop_split_url();
    $url['path'] = $this->getUrlPath();

    // Список задействованных дней.
    // FIXME: publishing
    $days = mcms::db()->getResultsKV("day", "count", "SELECT DAY(`n`.`created`) AS `day`, COUNT(*) AS `count` FROM `node` `n` WHERE `n`.`id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` = :tid) AND YEAR(`n`.`created`) = :year AND MONTH(`n`.`created`) = :month AND `n`.`published` = 1 GROUP BY `day` ORDER BY `day`", array(':tid' => $root, ':year' => $year, ':month' => $month));

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
          $result .= "<a href='". bebop_combine_url($url) ."'>{$day}</a>";
        }

        $result .= "</td>";
      }

      $result .= "</tr>";
    }

    $result .= "</table>";
    return $result;
  }

  private function getUrlPath()
  {
    $path = $this->ctx->ppath;

    if (null !== $this->ctx->section_id)
      $path[] = $this->ctx->section_id;

    $rc = '/'. join('/', $path) .'/';
    return $rc;
  }
};
