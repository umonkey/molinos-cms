<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AccessLogModule extends Widget implements iAdminWidget, iDashboard, iModuleConfig, iRequestHook
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Статистика обращений',
      'description' => 'Показывает статистику пользовательских запросов.',
      'hidden' => true,
      );
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    return $this->options = $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['mode']), $options);
  }

  protected function onGetDefault(array $options)
  {
    return parent::formRender('system-event-log-list');

    $result = array();
    $where = '';
    $pdo = mcms::db();
    $params = array();

    if (!empty($options['operation'])) {
      $where = 'WHERE `l`.`operation` = :op';
      $params['op'] = $options['operation'];
    }

    // Выбираем записи
    $sql = "SELECT `l`.*, `node`.`class`, `nr`.`name` AS `title` FROM `node__log` `l` "
      ."LEFT JOIN `node` ON `node`.`id` = `l`.`nid` "
      ."LEFT JOIN `node__rev` `nr` ON `node`.`rid` = `nr`.`rid` {$where} ORDER BY `l`.`lid` DESC";

    $pagerSql = "SELECT COUNT(*) FROM `node__log` `l` {$where}";

    // Поиск по полям
    if (!empty($options['search'])) {
      // Критерий
      $searchStr = "CONCAT(title, ip, operation, username) LIKE :search";
      $params['search'] = '%'. $options['search'] .'%';

      // Обновляем запрос в соответствии с критериями
      $sql = "SELECT * FROM ({$sql}) AS `log_list` WHERE {$searchStr}";
      // Пейджер тоже должен показывать страницы по результатам поиска
      $pagerSql = "SELECT COUNT(*) FROM ({$sql}) AS `log_list`";
    }
    $sql .= " LIMIT ". ($options['page'] - 1) * $options['limit'] .", {$options['limit']}";

    // Общее количество записей среди результатов поиска
    $count = $pdo->getResult($pagerSql, $params);

    // Выводим пэйджер.
    $result['pager'] = $this->getPager($count, $options['page'], $options['limit']);

    $result['entries'] = $pdo->getResults($sql, $params);

    return $result;
  }

  protected function onGetDownload(array $options)
  {
    $output = "Запрос;Количество\n";

    $data = mcms::db()->getResults("SELECT `query`, COUNT(`query`) AS `count` FROM `node__log` WHERE `operation` = 'search' AND `query` IS NOT NULL GROUP BY `query` ORDER BY `count` DESC");

    foreach ($data as $row) {
      $output .= str_replace(';', ",", $row['query']) .';'. $row['count'] ."\n";
    }

    $output = iconv('utf-8', 'windows-1251', $output);

    ini_set('zlib.output_compression', 0);
    header('Content-Type: application/vnd.ms-excel; charset=windows-1251');
    header('Content-Length: '. strlen($output));
    header('Content-Disposition: attachment; filename="Search Queries for '. $_SERVER['HTTP_HOST'] .'.csv"');

    die($output);
  }

  // РАБОТА С ФОРМАМИ.

  public function formGet($id)
  {
    switch ($id) {
    case 'system-event-log-list':
      $columns = array(
        'timestamp' => t('Время'),
        'username' => t('Пользователь'),
        'ip' => t('Адрес'),
        'operation' => t('Действие'),
        'title' => t('Заголовок'),
        'type' => t('Тип объекта'),
        );

      if (!$this->isGlobal())
        unset($columns['username']);

      $form = new Form(array(
        'title' => $this->isGlobal() ? t('Журнал событий') : t('Журнал ваших действий'),
        ));

      if (null !== $this->options['ip'] or null !== $this->options['operation'] or null !== $this->options['user'])
        $form->addControl(new InfoControl(array(
          'text' => t("Вы просматриваете отфильтрованный список событий; чтобы вернуться к полному списку, нужно <a href='@resetlink'>очистить параметры фильтрации</a>.",
            array('@resetlink' => '/admin/logs/')),
          )));
      else {
        $links = array();

        foreach (array('create' => t('добавление'), 'update' => t('изменение'), 'delete' => t('удаление'), 'undelete' => t('восстановление'), 'erase' => t('полное удаление'), 'search' => t('поиск'), 'cron' => t('периодические задачи')) as $k => $v)
          $links[] = l($v, array($this->getInstanceName() => array('op' => $k)));

        $form->addControl(new InfoControl(array(
          'text' => t("Вы просматриваете полный список событий; вы можете отфильтровать его по операциям: ") . join(', ', $links) .'.',
          )));
      }

      /*
      $form->addControl(new DocSearchControl(array(
        'value' => 'syslog_search',
        )));
      */

      $form->addControl(new DocListControl(array(
        'value' => 'syslog_list',
        'columns' => $columns,
        )));

      $form->addControl(new PagerControl(array(
        'value' => 'syslog_pager',
        'widget' => $this->getInstanceName(),
        'showempty' => true,
        )));

      return $form;
    }
  }

  public function formGetData($id)
  {
    switch ($id) {
    case 'system-event-log-list':
      $data = array();

      $pdo = mcms::db();

      $params = array();
      $where = $this->getFilter($params);

      $limit = sprintf("%d, %d", 
        $this->options['limit'] * ($this->options['page'] - 1),
        $this->options['limit']);

      $sql1 = "SELECT COUNT(*) FROM `node__log`". $where;
      $sql2 = "SELECT `node__log`.*, `node`.`class` AS `type`, `node__rev`.`name` AS `title` FROM `node__log` LEFT JOIN `node` ON `node`.`id` = `node__log`.`nid` LEFT JOIN `node__rev` ON `node__rev`.`rid` = `node`.`rid`{$where} ORDER BY `lid` DESC LIMIT ". $limit;

      $data['syslog_pager']['total'] = $pdo->getResult($sql1, $params);
      $data['syslog_pager']['page'] = $this->options['page'];
      $data['syslog_pager']['limit'] = $this->options['limit'];

      $data['syslog_list'] = $pdo->getResults($sql2, $params);

      foreach ($data['syslog_list'] as $k => $v) {
        if (empty($v['nid']))
          $data['syslog_list'][$k]['title'] = mcms_plain($v['query']);
        elseif (!empty($v['nid']) and !empty($v['title']))
          $data['syslog_list'][$k]['title'] = "<a href='/admin/node/{$v['nid']}/edit/?destination=". urlencode($_SERVER['REQUEST_URI']) ."'>". mcms_plain($v['title']) ."</a>";

        $data['syslog_list'][$k]['ip'] = l($v['ip'], array($this->getInstanceName() => array('ip' => $v['ip'])));
        $data['syslog_list'][$k]['operation'] = l($v['operation'], array($this->getInstanceName() => array('op' => $v['operation'])));
        $data['syslog_list'][$k]['username'] = l($v['username'], array($this->getInstanceName() => array('user' => $v['username'])));

        $data['syslog_list'][$k]['published'] = true;
        $data['syslog_list'][$k]['internal'] = true;
      }

      return $data;
    }
  }

  private function isGlobal()
  {
    return mcms::user()->hasGroup('Site Managers');
  }

  private function getFilter(array &$params)
  {
    $where = array();

    if (!$this->isGlobal()) {
      $where[] = "`node__log`.`uid` = :uid";
      $params[':uid'] = mcms::user()->getUid();
    }

    if (null !== $this->options['operation']) {
      $where[] = "`node__log`.`operation` = :op";
      $params[':op'] = $this->options['operation'];
    }

    if (null !== $this->options['ip']) {
      $where[] = "`node__log`.`ip` = :ip";
      $params[':ip'] = $this->options['ip'];
    }

    if (null !== $this->options['user']) {
      $where[] = "`node__log`.`username` = :user";
      $params[':user'] = $this->options['user'];
    }

    if (empty($where))
      return '';

    return ' WHERE '. join(' AND ', $where);
  }

  public static function getDashboardIcons()
  {
    $icons = array();
    $user = mcms::user();

    if ($user->hasGroup('Access Managers')) {
      $icons[] = array(
        'group' => 'Статистика',
        'img' => 'img/dashboard-task-logs.gif',
        'href' => '/admin/statistics/access/',
        'title' => t('Доступ к контенту'),
        'description' => t('Просмотр статистики доступа.'),
        );
    }

    return $icons;
  }

  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new SetControl(array(
      'value' => 'config_options',
      'label' => t('Отслеживаемые запросы'),
      'options' => array(
        'section' => t('К разделам'),
        'document' => t('К документам'),
        ),
      )));

    return $form;
  }

  // Обработка статистики
  public static function hookRequest(RequestContext $ctx = null)
  {
    if (null !== $ctx) {
      $conf = mcms::modconf('accesslog');

      if (!empty($conf['options']) and is_array($conf['options'])) {
        $sth = mcms::db()->prepare("INSERT INTO `node__astat` (`nid`, `timestamp`, `ip`, `referer`) VALUES (:nid, UTC_TIMESTAMP(), :ip, :referer)");

        $args = array(
          ':ip' => empty($_SERVER['REMOTE_ADDR']) ? null : $_SERVER['REMOTE_ADDR'],
          ':referer' => empty($_SERVER['HTTP_REFERER']) ? null : $_SERVER['HTTP_REFERER'],
          );

        if (in_array('section', $conf['options']) and isset($ctx->section_id)) {
          $args[':nid'] = $ctx->section_id;
          $sth->execute($args);
        }

        if (in_array('document', $conf['options']) and isset($ctx->document_id)) {
          $args[':nid'] = $ctx->document_id;
          $sth->execute($args);
        }
      }
    }
  }

  public static function hookPostInstall()
  {
    $t = new TableInfo('node__astat');

    if (!$t->exists()) {
      $t->columnSet('id', array(
        'type' => 'int(10) unsigned',
        'required' => true,
        'key' => 'pri',
        'autoincrement' => true,
        ));
      $t->columnSet('timestamp', array(
        'type' => 'datetime',
        ));
      $t->columnSet('nid', array(
        'type' => 'int(10) unsigned',
        'required' => false,
        'key' => 'mul',
        ));
      $t->columnSet('ip', array(
        'type' => 'varchar(15)',
        'required' => true,
        'key' => 'mul',
        ));
      $t->columnSet('referer', array(
        'type' => 'varchar(255)',
        'key' => 'mul',
        ));

      $t->commit();
    }
  }
};
