<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RatingWidget extends Widget implements iNodeHook
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => t('Оценка документов'),
      'description' => t('Выводит и обрабатывает форму голосования за документы.'),
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new BoolControl(array(
      'value' => 'config_anonymous',
      'label' => t('Разрешить анонимное голосование'),
      'description' => t('По умолчанию анонимные пользователи не могут голосовать, им будет предложено залогиниться или зарегистрироваться.&nbsp; Вы можете принудительно разрешить им голосовать.'),
      )));

    $form->addControl(new EnumControl(array(
      'value' => 'config_mode',
      'label' => t('Форма для голосования'),
      'options' => array(
        '' => t('отсутствует'),
        'check' => t('да / нет'),
        'rate' => '1...5',
        ),
        'required' => true,
      )));

    return $form;
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $halt = false;

    $options = parent::getRequestOptions($ctx);

    $options['#nocache'] = true;
    $options['action'] = $ctx->get('action', 'status');
    $options['vote'] = $ctx->get('vote');

    if (null === ($options['node'] = $ctx->document_id))
      $halt = true;

    if (null !== ($options['rate'] = $ctx->get('rate')))
      $options['action'] = 'rate';

    if ($halt)
      throw new WidgetHaltedException();

    return $this->options = $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['action']), $options);
  }

  protected function onGetStatus(array $options)
  {
    $result = array(
      'average' => mcms::db()->getResult("SELECT AVG(`rate`) FROM `node__rating` WHERE `nid` = :nid AND `rate` <> 0", array(':nid' => $this->options['node'])),
      'count' => mcms::db()->getResult("SELECT COUNT(*) FROM `node__rating` WHERE `nid` = :nid AND `rate` <> 0", array(':nid' => $this->options['node'])),
      'user' => mcms::db()->getResult("SELECT `rate` FROM `node__rating` WHERE `nid` = :nid AND `uid` = :uid", array(':nid' => $this->options['node'], ':uid' => mcms::user()->id)),
      );

    bebop_on_json($result);

    return $result;
  }

  // Вывод статистики.
  protected function onGetList(array $options)
  {
    $result = array();

    $pdo = mcms::db();
    $user = mcms::user();

    // Статистика по текущему документу.
    $stats = $pdo->getResult("SELECT AVG(`rate`) FROM `node__rating` WHERE `nid` = :nid", array(':nid' => $options['node']));

    if ($this->checkUserHasVote())
      $output = $this->getStatsForm($stats, true);
    elseif ($this->user->id == 0 and empty($this->anonymous))
      $output = $this->getStatsForm($stats, false);
    else
      $output = $this->getWorkingForm($stats, false);

    // Заворачиваем в див.
    $result['html'] = "<div class='rating-widget' id='rating-widget-{$this->me->name}'>". $output ."</div>";

    return $result;
  }

  // Добавление голоса.
  protected function onGetVote(array $options)
  {
    if ($this->checkUserHasVote())
      throw new ForbiddenException(t("Вы уже голосовали за этот документ."));

    $pdo = mcms::db();

    $params = array(
      ':nid' => $this->ctx->document_id,
      ':uid' => $this->user->id,
      ':ip' => $_SERVER['REMOTE_ADDR'],
      ':rate' => $options['vote'] / 5,
      );

    if ($this->user->id)
      $pdo->exec("REPLACE INTO `node__rating` (`nid`, `uid`, `ip`, `rate`) VALUES (:nid, :uid, :ip, :rate)", $params);
    else
      $pdo->exec("INSERT INTO `node__rating` (`nid`, `uid`, `ip`, `rate`) VALUES (:nid, :uid, :ip, :rate)", $params);

    $this->setUserVoted();

    // Сообщаем скриптам статус.
    bebop_on_json(array(
      'status' => 'ok',
      ));

    // Редиректим простых пользователей обратно.
    $url = bebop_split_url();
    $url['args'][$this->getInstanceName()] = null;

    $destination = empty($url['args']['destination'])
      ? bebop_combine_url($url)
      : $url['args']['destination'];

    exit(mcms::redirect($destination));
  }

  protected function onGetRate(array $options)
  {
    $result = array(
      'status' => 'ok',
      'message' => t('Your vote had been added.'),
      );

    $this->voteCast();

    bebop_on_json($result);

    $url = bebop_split_url();
    $url['args'][$this->getInstanceName()] = null;
    mcms::redirect($url);
  }

  // Формирование формы со статистикой.
  private function getStatsForm($stats, $voted)
  {
    $comment = $voted
      ? t('вы уже голосовали')
      : t('вам нельзя голосовать');

    $output = t('Текущий рейтинг: %rate (%comment).', array(
      '%rate' => floatval($stats),
      '%comment' => $comment,
      ));

    $output = "<div class='rating'>". $output ."</div>";
    return $output;
  }

  private function getWorkingForm($stats, $voted)
  {
    $links = array();

    for ($idx = 1; $idx <= 5; $idx++) {
      $links[] = l($idx, array($this->getInstanceName() => array(
        'action' => 'vote',
        'vote' => $idx,
        )));
    }

    $output = t('Ваша оценка: '). join(' &nbsp; ', $links);
    return $output;
  }

  // Возвращает true, если пользователь уже голосовал.
  private function checkUserHasVote()
  {
    $skey = 'already_voted_with_'. $this->getInstanceName();

    $rate = (array)mcms::session('rate');

    // Мы кэшируем состояние в сессии для всех пользователей, поэтому проверяем в первую очередь.
    if (!empty($rate[$skey]))
      return true;

    // Анонимных пользователей считаем по IP, зарегистрированных -- по идентификатору.
    if ($this->user->id == 0)
      $status = 0 != mcms::db()->getResult("SELECT COUNT(*) FROM `node__rating` WHERE `nid` = :nid AND `uid` = 0 AND `ip` = :ip", array(':nid' => $this->ctx->document_id, ':ip' => $_SERVER['REMOTE_ADDR']));
    else
      $status = 0 != mcms::db()->getResult("SELECT COUNT(*) FROM `node__rating` WHERE `nid` = :nid AND `uid` = :uid", array(':nid' => $this->ctx->document_id, ':uid' => $this->user->id));

    // Сохраняем в сессии для последующего быстрого доступа.

    $rate[$skey] = $status;
    mcms::session('rate', $rate);

    return $status;
  }

  // Запрещаем повторное голосование.
  private function setUserVoted()
  {
    $rate = (array)mcms::session('rate');
    $rate['already_voted_with_'. $this->getInstanceName()] = true;
    mcms::session('rate', $rate);
  }

  private function voteCast()
  {
    $db = mcms::db();

    $db->exec("DELETE FROM `node__rating` WHERE `nid` = :nid AND `uid` = :uid", array(
      ':nid' => $this->options['node'],
      ':uid' => mcms::user()->id,
      ));
    $db->exec("INSERT INTO `node__rating` (`nid`, `uid`, `ip`, `rate`) VALUES (:nid, :uid, :ip, :rate)", array(
      ':nid' => $this->options['node'],
      ':uid' => mcms::user()->id,
      ':ip' => $_SERVER['REMOTE_ADDR'],
      ':rate' => $this->options['rate'],
      ));
  }

  public static function hookNodeUpdate(Node $node, $op)
  {
    if ($op == 'erase')
      mcms::db()->exec("DELETE FROM `node_rating` WHERE `nid` = :nid OR `uid` = :uid", array(':nid' => $node->id, ':uid' => $node->id));
  }
};
