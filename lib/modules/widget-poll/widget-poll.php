<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class PollWidget extends Widget
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
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['#nocache'] = true;
    $options['action'] = $ctx->get('action', 'default');

    if (!($options['section'] = $this->fixed))
      $options['section'] = $ctx->section_id;

    return $this->options = $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    if (null === ($poll = $this->getCurrentPoll()))
      return null;

    if (!$this->checkUserVoted())
      return parent::formRender('vote-form');

    else {
      $options = array(
        );

      foreach (self::getPollOptions($poll) as $k => $v)
        $options[$k] = array('text' => $v, 'count' => 0);

      foreach (PDO_Singleton::getInstance()->getResultsKV('option', 'count', 'SELECT `option`, COUNT(*) AS `count` FROM `node__poll` WHERE `nid` = :nid GROUP BY `option`', array(':nid' => $poll->id)) as $k => $v)
        if (array_key_exists(intval($k), $options))
          $options[$k]['count'] = intval($v);

      return array(
        'title' => $poll->name,
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

        if ($node->mode == 'multi' or !empty($node->multiple))
          $class = 'SetControl';
        else
          $class = 'EnumRadioControl';

        $form = new Form(array());
        $form->addControl(new $class($options));
        $form->addControl(new SubmitControl(array(
          'text' => t('Проголосовать'),
          )));
      }

      break;
    }

    return $form;
  }

  public function formGetData($id)
  {
    return array();
  }

  public function formProcess($form_id, array $data)
  {
    switch ($form_id) {
    case 'vote-form':
      if (null !== ($poll = $this->getCurrentPoll())) {
        if ($this->checkUserVoted($poll))
          return;

        if (!($uid = AuthCore::getInstance()->getUser()->getUid()))
          $uid = null;

        if (!empty($data['vote'])) {
          foreach ((array)$data['vote'] as $vote)
            PDO_Singleton::getInstance()->exec("INSERT INTO `node__poll` (`nid`, `uid`, `ip`, `option`) VALUES (:nid, :uid, :ip, :option)", array(
              ':nid' => $poll->id,
              ':uid' => $uid,
              ':ip' => $_SERVER['REMOTE_ADDR'],
              ':option' => $vote,
              ));
        }
      }

      break;
    }
  }

  protected function getCurrentPoll()
  {
    $filter = array(
      'class' => 'poll',
      '#sort' => array(
        'id' => 'desc',
        ),
      '#files' => false,
      );

    if (!empty($this->options['section']))
      $filter['tags'] = $this->options['section'];

    $nodes = Node::find($filter, 1);

    if (!empty($nodes))
      return array_shift($nodes);

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

    $uid = AuthCore::getInstance()->getUser()->getUid();
    $pdo = PDO_Singleton::getInstance();

    if (empty($uid)) {
      if ($pdo->getResult("SELECT COUNT(*) FROM `node__poll` WHERE `nid` = :nid AND `uid` IS NULL AND `ip` = :ip", array(':nid' => $poll->id, ':ip' => $_SERVER['REMOTE_ADDR'])))
        return true;
    } elseif ($pdo->getResult("SELECT COUNT(*) FROM `node__poll` WHERE `nid` = :nid AND `uid` = :uid AND `ip` = :ip", array(':nid' => $poll->id, ':uid' => $uid, ':ip' => $_SERVER['REMOTE_ADDR']))) {
      return true;
    }

    return false;
  }

  // Удаляем мусор при удалении документов.
  public static function hookNodeUpdate(Node $node, $op)
  {
    if ($op == 'erase')
      PDO_Singleton::getInstance()->exec("DELETE FROM `node__poll` WHERE `nid` = :nid OR `uid` = :uid", array(':nid' => $node->id, ':uid' => $node->id));
  }
};

class PollNode extends Node implements iContentType
{
  public function getAccess()
  {
    $data = parent::getAccess();

    if (null === $this->id) {
      $data['Visitors']['r'] = 1;
      $data['Content Managers']['r'] = 1;
      $data['Content Managers']['u'] = 1;
      $data['Content Managers']['d'] = 1;
    }

    return $data;
  }
};
