<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class StatusAdminWidget extends Widget
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

    $cron = $pdo->getResult("SELECT DATEDIFF(NOW(), MAX(timestamp)) FROM node__log WHERE operation = 'cron'");

    if (null === $cron or $cron > 0)
      $result['hints'][] = t("Планировщик заданий давно не запускался. Причиной этого может стать неработающая рассылка новостей, импорт данных из других источников и многое другое. Вы можете попросить администратора сервера настроить <a href='@cron'>cron</a> на периодический <a class='manualcron' target='_blank' href='@link'>запуск планировщика</a>, или делать это иногда вручную.",
        array('@link' => "http://{$_SERVER['HTTP_HOST']}/cron.php",
          '@cron' => 'http://ru.wikipedia.org/wiki/Cron'));

    return $result;
  }

  protected function onGetFixrights(array $options)
  {
    if (empty($_GET['destination']))
      $_GET['destination'] = 'admin';

    mcms::redirect($_GET['destination']);
  }
};
