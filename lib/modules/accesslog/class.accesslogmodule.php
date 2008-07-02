<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AccessLogModule extends Widget implements iAdminMenu, iModuleConfig, iRequestHook, iAdminUI
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
  public static function onGet(RequestContext $ctx)
  {
    $tmp = new AccessLogListHandler($ctx);
    return $tmp->getHTML();
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
            array('@resetlink' => 'admin/logs/')),
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
      $sql2 = "SELECT `node__log`.`lid` as `lid`, `node__log`.`timestamp` as `timestamp`, "
        ."`node__log`.`nid` as `nid`, `node__log`.`uid` as `uid`,`node__log`.`username` as `username`, "
        ."`node__log`.`ip` as `ip`, `node__log`.`operation` as `operation`, "
        ."`node__log`.`message` as `message`, `node`.`class` AS `type`, `node__rev`.`name` AS `title` FROM `node__log` LEFT JOIN `node` ON `node`.`id` = `node__log`.`nid` LEFT JOIN `node__rev` ON `node__rev`.`rid` = `node`.`rid` {$where} ORDER BY `lid` DESC LIMIT ". $limit;

      $data['syslog_pager']['total'] = $pdo->getResult($sql1, $params);
      $data['syslog_pager']['page'] = $this->options['page'];
      $data['syslog_pager']['limit'] = $this->options['limit'];

      $data['syslog_list'] = $pdo->getResults($sql2, $params);

      foreach ($data['syslog_list'] as $k => $v) {
        if (empty($v['nid']))
          $data['syslog_list'][$k]['title'] = mcms_plain($v['query']);
        elseif (!empty($v['nid']) and !empty($v['title']))
          $data['syslog_list'][$k]['title'] = l(mcms_plain($v['title']), "admin/node/{$v['nid']}/edit/?destination=CURRENT");

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
    return mcms::user()->hasAccess('u', 'moduleinfo');
  }

  private function getFilter(array &$params)
  {
    $where = array();

    if (!$this->isGlobal()) {
      $where[] = "`node__log`.`uid` = :uid";
      $params[':uid'] = mcms::user()->id;
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

  public static function getMenuIcons()
  {
    $icons = array();
    $user = mcms::user();

    if ($user->hasAccess('u', 'user')) {
      $icons[] = array(
        'group' => 'statistics',
        'href' => 'admin?module=accesslog',
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

      try {
        if (!empty($conf['options']) and is_array($conf['options'])) {
          if (in_array('section', $conf['options']) and isset($ctx->section_id))
            self::logNode($ctx->section_id);

          if (in_array('document', $conf['options']) and isset($ctx->document_id))
            self::logNode($ctx->document_id);
        }
      } catch (PDOException $e) {
        // Обычно здесь обламываемя при обращении к несуществующему урлу.
      }
    }
  }

  public static function logNode($nid)
  {
    $args = array(
      ':ip' => empty($_SERVER['REMOTE_ADDR']) ? null : $_SERVER['REMOTE_ADDR'],
      ':referer' => empty($_SERVER['HTTP_REFERER']) ? null : $_SERVER['HTTP_REFERER'],
      ':nid' => $nid,
      );

    mcms::db()->exec("INSERT INTO `node__astat` (`nid`, `timestamp`, `ip`, `referer`) VALUES (:nid, UTC_TIMESTAMP(), :ip, :referer)", $args);
  }

  public static function hookPostInstall()
  {
    $t = new TableInfo('node__astat');

    if (!$t->exists()) {
      $t->columnSet('id', array(
        'type' => 'int',
        'required' => true,
        'key' => 'pri',
        'autoincrement' => true,
        ));
      $t->columnSet('timestamp', array(
        'type' => 'datetime',
        ));
      $t->columnSet('nid', array(
        'type' => 'int',
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
