<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class PollWidget extends Widget implements /* iNodeHook, */ iModuleConfig
{
  private $options;

  public static function getWidgetInfo()
  {
    return array(
      'name' => t('Голосование'),
      'description' => t('Выводит и обрабатывает форму для опроса пользователей.'),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/PollWidget',
      );
  }

  public static function getConfigOptions()
  {
    return array(
      'fixed' => array(
        'type' => 'EnumControl',
        'label' => t('Показывать последний опрос из раздела'),
        'options' => Node::getSortedList('tag'),
        'default' => t('Текущего (из пути или свойств страницы)'),
        'required' => true,
        ),
      );
  }

  // Препроцессор параметров.
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $options['#cache'] = false;
    $options['action'] = $this->get('action', 'default');

    if (!($options['section'] = $this->fixed))
      $options['section'] = $ctx->section->id;

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    if (null === ($poll = $this->getCurrentPoll($options)))
      return null;

    // FIXME!
    $this->options = $options;

    if (!$this->checkUserVoted($options)) {
      return array(
        'mode' => 'form',
        'node' => $poll->getRaw(),
        'options' => self::getPollOptions($poll),
        'schema' => $poll->getSchema(),
        'form' => parent::formRender('vote-form', $poll),
        );
    }

    else {
      $total = 0;
      $options = array(
        );

      foreach (self::getPollOptions($poll) as $k => $v)
        $options[$k] = array('text' => $v, 'count' => 0);

      foreach ($this->ctx->db->getResultsKV('option', 'count', 'SELECT `option`, COUNT(*) AS `count` FROM `node__poll` WHERE `nid` = :nid GROUP BY `option`', array(':nid' => $poll->id)) as $k => $v) {
        if (array_key_exists(intval($k), $options))
          $options[$k]['count'] = intval($v);

        // Суммируем голоса, потом можно будет подсчитать процент.
        $total += $options[$k]['count'];
      }

      return array(
        'title' => $poll->name,
        'mode' => 'results',
        'options' => $options,
        'total' => $total,
        );
    }
  }

  public function formGet($id)
  {
    $form = null;

    switch ($id) {
    case 'vote-form':
      if (null !== ($node = $this->getCurrentPoll($this->options))) {
        $options = array(
          'label' => $node->name,
          'required' => true,
          'options' => self::getPollOptions($node),
          'value' => 'vote',
          );

        if ($node->mode == 'multi')
          $class = 'SetControl';
        else
          $class = 'EnumRadioControl';

        $form = new Form(array());
        $form->addControl(new $class($options));
        $form->addControl(new SubmitControl(array(
          'text' => t('Проголосовать'),
          )));

        $form->action = '?q=poll.rpc&action=vote'
          . '&nid=' . $node->id
          . '&destination=CURRENT';
      }

      break;
    }

    return $form;
  }

  public function formGetData($id)
  {
    return array();
  }

  protected function getCurrentPoll(array $options)
  {
    $nodes = Node::find($filter = array(
      'class' => 'poll',
      'tags' => $options['section'],
      '#sort' => '-id',
      ), 1, 0);

    if (!empty($nodes))
      return $nodes[key($nodes)];

    return null;
  }

  protected static function getPollOptions(Node $poll)
  {
    $options = array();

    foreach (preg_split('/[\r\n]+/', $poll->answers) as $idx => $name)
      $options[$idx + 1] = $name;

    return $options;
  }

  protected function checkUserVoted(array $options, Node $poll = null)
  {
    if (null === $poll)
      if (null === ($poll = $this->getCurrentPoll($options)))
        return true;

    if (!($uid = $this->ctx->user->id)) {
      if ($this->ctx->db->getResult("SELECT COUNT(*) FROM `node__poll` WHERE `nid` = :nid AND `uid` IS NULL AND `ip` = :ip", array(':nid' => $poll->id, ':ip' => $_SERVER['REMOTE_ADDR'])))
        return true;
    } elseif ($this->ctx->db->getResult("SELECT COUNT(*) FROM `node__poll` WHERE `nid` = :nid AND `uid` = :uid AND `ip` = :ip", array(':nid' => $poll->id, ':uid' => $uid, ':ip' => $_SERVER['REMOTE_ADDR']))) {
      return true;
    }

    return false;
  }

  // Удаляем мусор при удалении документов.
  public static function hookNodeUpdate(Node $node, $op)
  {
    if ($op == 'erase')
      $node->getDB()->exec("DELETE FROM `node__poll` WHERE `nid` = :nid OR `uid` = :uid", array(':nid' => $node->id, ':uid' => $node->id));
  }

  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new BoolControl(array(
      'value' => 'config_anonymous',
      'label' => t('Разрешить анонимные ответы'),
      'description' => t('Эта настройка влияет на все существующие опросы.'),
      )));

    return $form;
  }
};
