<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class StatusAdminWidget extends Widget implements iAdminWidget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);

    $this->groups = array(
      'Visitors',
      );
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Мониторинг статуса',
      'description' => 'Проверяет статус системы, выдаёт рекомендации.',
      );
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['action'] = $ctx->get('action', 'status');
    $options['#nocache'] = true;

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['action']), $options);
  }

  protected function onGetStatus(array $options)
  {
    $result = array(
      'hints' => array(),
      );

    $pdo = mcms::db();

    if ($this->user->hasGroup('Site Managers') and $pdo->getResult("SELECT COUNT(*) FROM `node` WHERE `rid` IS NULL")) {
      $pdo->exec("DELETE FROM `node` WHERE `rid` IS NULL");
      $result['hints'][] = "Были обнаружены заголовки документов без ревизий.&nbsp; Они были удалены.&nbsp; Такие объекты появляются при возникновении ошибок во время сохранения документов, о которых информируется создающий их пользователь, так что волноваться не нужно.";
    }

    if ($this->user->hasGroup('Access Managers') and Node::count(array('#special' => 'lost'))) {
      $result['hints'][] = t("В системе обнаружены объекты без прав доступа.&nbsp; Они не отображаются на сайте, они не видны ни в наполнении, ни где-либо ещё.&nbsp; Вы можете <a href='@listlink'>перейти к списку</a> этих документов или сразу <a href='@fixlink'>сделать их доступными</a> менеджерам контента.", array(
        '@listlink' => '/admin/content/?BebopContentList.special=lost',
        '@fixlink' => '/admin/?BebopStatus.action=fixrights',
        ));
    }

    $cron = $pdo->getResult("SELECT DATEDIFF(NOW(), MAX(timestamp)) FROM node__log WHERE operation = 'cron'");

    if (null === $cron or $cron > 0)
      $result['hints'][] = t("Планировщик заданий давно не запускался. Причиной этого может стать неработающая рассылка новостей, импорт данных из других источников и многое другое. Вы можете попросить администратора сервера настроить <a href='@cron'>cron</a> на периодический <a class='manualcron' target='_blank' href='@link'>запуск планировщика</a>, или делать это иногда вручную.",
        array('@link' => "http://{$_SERVER['HTTP_HOST']}/cron.php",
          '@cron' => 'http://ru.wikipedia.org/wiki/Cron'));

    if (!ini_get('session.save_path')) {
      $result['hints'][] = t('Каталог для хранения сессий не определён. Это приведёт к тому, что сессии будут закрываться преждевременно, а в некоторых случаях — к неожиданной авторизации на других сайтах, работающих на этом сервере. Для устранения этой проблемы нужно добавить в файл .htaccess следующую запись:<br />php_value session.save_path %path', array(
        '%path' => getcwd() .'/tmp/sessions',
        ));
    }

    /*
    $ncount = $pdo->getResult("SELECT COUNT(*) FROM `node`");
    $rcount = $pdo->getResult("SELECT COUNT(*) FROM `node__rev`");

    if (($ncount > 100) and ($rel = intval($rcount / $ncount)) > 10)
      $result['hints'][] = t("Сейчас в базе данных %nodecount документов и %revcount ревизий; это примерно %rel ревизий на документ, что достаточно много.", array(
        '%nodecount' => $ncount,
        '%revcount' => $rcount,
        '%rel' => $rel,
        ));
    */

    return $result;
  }

  protected function onGetFixrights(array $options)
  {
    $this->user->checkGroup('Access Managers');

    if (!ini_get('safe_mode'))
      set_time_limit(0);

    while (count($nodes = Node::find(array('#special' => 'lost'), 10))) {
      foreach ($nodes as $node)
        $node->setAccess(array(
          'Content Managers' => array('r', 'u', 'd'),
          ));
    }

    mcms::db()->commit();

    if (empty($_GET['destination']))
      $_GET['destination'] = '/admin/';

    bebop_redirect($_GET['destination']);
  }
};
