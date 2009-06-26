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
      );
  }

  public static function getConfigOptions()
  {
    return array(
      'fixed' => array(
        'type' => 'EnumControl',
        'label' => t('Показывать последний опрос из раздела'),
        'options' => TagNode::getTags('select'),
        'default' => t('Текущего (из пути или свойств страницы)'),
        'required' => true,
        ),
      'random' => array(
        'type' => 'BoolControl',
        'label' => t('Выводить случайный доступный опрос'),
        ),
      );
  }

  // Препроцессор параметров.
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $options['#cache'] = false;
    $options['action'] = $ctx->get('action', 'default');

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
        'options' => $poll->getOptions(),
        'schema' => $poll->getSchema(),
        'form' => parent::formRender('vote-form', $poll),
        );
    }

    else {
      $total = 0;
      $options = array(
        );

      foreach ($poll->getOptions() as $k => $v)
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
          'options' => $node->getOptions(),
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
    $filter = array(
      'class' => 'poll',
      'tags' => $options['section'],
      '#sort' => '-id',
      'published' => 1,
      );

    if ($this->random) {
      if ($uid = mcms::user()->id)
        $ids = mcms::db()->getResultsV("nid", "SELECT DISTINCT(`nid`) FROM `node__poll` WHERE `uid` = ?", array($uid));
      else
        $ids = mcms::db()->getResultsV("nid", "SELECT DISTINCT(`nid`) FROM `node__poll` WHERE `ip` = ?", array($_SERVER['REMOTE_ADDR']));
      if (!empty($ids))
        $filter['-id'] = $ids;
    }

    $nodes = Node::find($filter, 1, 0);

    if (!empty($nodes))
      return $nodes[key($nodes)];

    return null;
  }

  protected function checkUserVoted(array $options, Node $poll = null)
  {
    if (null === $poll)
      if (null === ($poll = $this->getCurrentPoll($options)))
        return true;

    if (!($uid = mcms::user()->id)) {
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
      mcms::db()->exec("DELETE FROM `node__poll` WHERE `nid` = :nid OR `uid` = :uid", array(':nid' => $node->id, ':uid' => $node->id));
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

  public static function hookPostInstall()
  {
    $t = new TableInfo('node__poll');

    if (!$t->exists()) {
      $t->columnSet('nid', array(
        'type' => 'int',
        'required' => true,
        'key' => 'mul',
        ));
      $t->columnSet('uid', array(
        'type' => 'int',
        'required' => false,
        'key' => 'mul',
        ));
      $t->columnSet('ip', array(
        'type' => 'varchar(15)',
        'required' => true,
        'key' => 'mul',
        ));
      $t->columnSet('option', array(
        'type' => 'int',
        'required' => true,
        'key' => 'mul',
        ));

      $t->commit();
    }
  }
};
