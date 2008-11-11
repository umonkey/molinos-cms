<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class PollWidget extends Widget implements /* iNodeHook, */ iModuleConfig
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => t('Голосование'),
      'description' => t('Выводит и обрабатывает форму для опроса пользователей.'),
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_fixed',
      'label' => t('Показывать последний опрос из раздела'),
      'options' => TagNode::getTags('select'),
      'default' => t('Текущего (из пути или свойств страницы)'),
      'required' => true,
      )));

    return $form;
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
    if (null === ($poll = $this->getCurrentPoll()))
      return null;

    if (!$this->checkUserVoted()) {
      return array(
        'mode' => 'form',
        'node' => $poll->getRaw(),
        'schema' => $poll->schema(),
        'form' => parent::formRender('vote-form'),
        );
    }

    else {
      $options = array(
        );

      foreach (self::getPollOptions($poll) as $k => $v)
        $options[$k] = array('text' => $v, 'count' => 0);

      foreach (mcms::db()->getResultsKV('option', 'count', 'SELECT `option`, COUNT(*) AS `count` FROM `node__poll` WHERE `nid` = :nid GROUP BY `option`', array(':nid' => $poll->id)) as $k => $v)
        if (array_key_exists(intval($k), $options))
          $options[$k]['count'] = intval($v);

      return array(
        'title' => $poll->name,
        'mode' => 'results',
        'options' => $options,
        );
    }
  }

  public function formGet($id)
  {
    $form = null;

    switch ($id) {
    case 'vote-form':
      if (null !== ($node = $this->getCurrentPoll())) {
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

  protected function getCurrentPoll()
  {
    $nodes = Node::find($filter = array('class' => 'poll', 'tags' => $this->options['section'], '#sort' => array('created' => 'desc')), 1, 0);

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

  protected function checkUserVoted(Node $poll = null)
  {
    if (null === $poll)
      if (null === ($poll = $this->getCurrentPoll()))
        return true;

    if (!($uid = mcms::user()->id)) {
      if (mcms::db()->getResult("SELECT COUNT(*) FROM `node__poll` WHERE `nid` = :nid AND `uid` IS NULL AND `ip` = :ip", array(':nid' => $poll->id, ':ip' => $_SERVER['REMOTE_ADDR'])))
        return true;
    } elseif (mcms::db()->getResult("SELECT COUNT(*) FROM `node__poll` WHERE `nid` = :nid AND `uid` = :uid AND `ip` = :ip", array(':nid' => $poll->id, ':uid' => $uid, ':ip' => $_SERVER['REMOTE_ADDR']))) {
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
