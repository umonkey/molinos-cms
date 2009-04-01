<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class PollWidget extends Widget
{
  private $options;

  /**
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'class' => __CLASS__,
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
      $output = html::em('poll', array(
        'id' => $poll->id,
        'title' => $poll->name,
        'mode' => 'form',
        ), $poll->getOptionsXML());
    }

    else {
      $output = html::em('poll', array(
        'title' => $poll->name,
        'mode' => 'results',
        ), $poll->getOptionsXML());
    }

    return $output;
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
    $nodes = Node::find($this->ctx->db, $filter = array(
      'class' => 'poll',
      'tags' => $options['section'],
      '#sort' => '-id',
      ), 1, 0);

    if (!empty($nodes))
      return $nodes[key($nodes)]->getObject();

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

  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu()
  {
    return array(
      array(
        're' => 'admin/system/settings/poll',
        'title' => t('Опросы'),
        'method' => 'modman::settings',
        ),
      );
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.poll
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'anonymous' => array(
        'type' => 'BoolControl',
        'label' => t('Разрешить анонимные ответы'),
        'description' => t('Эта настройка влияет на все существующие опросы.'),
        ),
      ));
  }
};
