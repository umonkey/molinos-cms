<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RatingWidget extends Widget
{
  /**
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'class' => __CLASS__,
      'name' => t('Оценка документов'),
      'description' => t('Выводит и обрабатывает форму голосования за документы.'),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/RatingWidget',
      );
  }

  public static function getConfigOptions()
  {
    return array(
      'anonymous' => array(
        'type' => 'BoolControl',
        'label' => t('Разрешить анонимное голосование'),
        'description' => t('По умолчанию анонимные пользователи не могут голосовать, им будет предложено залогиниться или зарегистрироваться.&nbsp; Вы можете принудительно разрешить им голосовать.'),
        ),
      'mode' => array(
        'type' => 'EnumControl',
        'label' => t('Форма для голосования'),
        'options' => array(
          '' => t('отсутствует'),
          'check' => t('да / нет'),
          'rate' => '1...5',
          ),
        'required' => true,
        ),
      );
  }

  // Препроцессор параметров.
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $halt = false;

    $options['#cache'] = false;
    $options['action'] = $this->get('action', 'status');
    $options['vote'] = $this->get('vote');

    if (null === ($options['node'] = $ctx->document->id))
      $halt = true;

    if (null !== ($options['rate'] = $this->get('rate')))
      $options['action'] = 'rate';

    if ($halt)
      throw new WidgetHaltedException();

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['action']), $options);
  }

  protected function onGetStatus(array $options)
  {
    $result = $this->getStatus($options);

    bebop_on_json($result);

    return $result;
  }

  private function getStatus(array $options)
  {
    $result = array(
      'average' => $this->ctx->db->getResult("SELECT AVG(`rate`) FROM `node__rating` WHERE `nid` = :nid AND `rate` <> 0", array(':nid' => $options['node'])),
      'count' => $this->ctx->db->getResult("SELECT COUNT(*) FROM `node__rating` WHERE `nid` = :nid AND `rate` <> 0", array(':nid' => $options['node'])),
      'user' => $this->ctx->db->getResult("SELECT `rate` FROM `node__rating` WHERE `nid` = :nid AND `uid` = :uid", array(':nid' => $options['node'], ':uid' => $this->ctx->user->id)),
      );

    for ($idx = 1; $idx <= 5; $idx++)
      $result['rates'][$idx] = $this->ctx->db->getResult("SELECT COUNT(`nid`) FROM `node__rating` WHERE `nid` = :nid AND `rate` = :rate", array(':nid' => $options['node'], ':rate' => $idx));
 
    return $result;
  }

  // Вывод статистики.
  protected function onGetList(array $options)
  {
    $result = array();

    $pdo = $this->ctx->db;
    $user = $this->ctx->user;

    // Статистика по текущему документу.
    $stats = $pdo->getResult("SELECT AVG(`rate`) FROM `node__rating` WHERE `nid` = :nid", array(':nid' => $options['node']));

    if ($this->checkUserHasVote($options))
      $output = $this->getStatsForm($stats, true);
    elseif ($this->user->id == 0 and empty($this->anonymous))
      $output = $this->getStatsForm($stats, false);
    else
      $output = $this->getWorkingForm($stats, false);

    // Заворачиваем в див.
    $result['html'] = "<div class='rating-widget' id='rating-widget-{$this->me->name}'>". $output ."</div>";

    return $result;
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
      $links[] = html::link($idx, array($this->getInstanceName() => array(
        'action' => 'vote',
        'vote' => $idx,
        )));
    }

    $output = t('Ваша оценка: '). join(' &nbsp; ', $links);
    return $output;
  }

  // Возвращает true, если пользователь уже голосовал.
  private function checkUserHasVote(array $options)
  {
    $skey = 'already_voted_with_'. $this->getInstanceName() . "_{$options['node']}";

    $rate = (array)mcms::session('rate');

    // Мы кэшируем состояние в сессии для всех пользователей, поэтому проверяем в первую очередь.
    if (!empty($rate[$skey]))
      return true;

    // Анонимных пользователей считаем по IP, зарегистрированных -- по идентификатору.
    if ($this->user->id == 0)
      $status = 0 != $this->ctx->db->getResult("SELECT COUNT(*) FROM `node__rating` WHERE `nid` = :nid AND `uid` = 0 AND `ip` = :ip", array(':nid' => $this->ctx->document->id, ':ip' => $_SERVER['REMOTE_ADDR']));
    else
      $status = 0 != $this->ctx->db->getResult("SELECT COUNT(*) FROM `node__rating` WHERE `nid` = :nid AND `uid` = :uid", array(':nid' => $this->ctx->document->id, ':uid' => $this->user->id));

    // Сохраняем в сессии для последующего быстрого доступа.

    $rate[$skey] = $status;
    mcms::session('rate', $rate);

    return $status;
  }

  private function voteCast(array $options)
  {
    $db = $this->ctx->db;

    $db->exec("INSERT INTO `node__rating` (`nid`, `uid`, `ip`, `rate`) VALUES (:nid, :uid, :ip, :rate)", array(
      ':nid' => $options['node'],
      ':uid' => $this->ctx->user->id,
      ':ip' => $_SERVER['REMOTE_ADDR'],
      ':rate' => $options['rate'],
      ));
  }
};
