<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentFormWidget extends Widget
{
  protected $newcomment = null;

  public function __construct(Node $node)
  {
    if (empty($node->config))
      $node->config = array(
        'moderate' => true,
        );

    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Комментарий (добавление)',
      'description' => 'Позволяет пользователям оставлять комментарии.',
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $fields = array();
    $schema = Node::create('comment')->schema();

    foreach ($schema['fields'] as $k => $v)
      $fields[$k] = $v['label'];

    $form->addControl(new BoolControl(array(
      'value' => 'config_moderated',
      'label' => t('Премодерация'),
      'description' => t('Комментарии будут сохраняться, но на сайте они будут отображаться только после одобрения модератором.'),
      )));

    $form->addControl(new SetControl(array(
      'value' => 'config_allowed_types',
      'label' => t('Комментируемые документы'),
      'options' => self::getTypes(),
      )));

    $form->addControl(new SetControl(array(
      'value' => 'config_hide_anon',
      'label' => t('Скрыть от анонимных'),
      'options' => $fields,
      )));

    $form->addControl(new SetControl(array(
      'value' => 'config_hide_user',
      'label' => t('Скрыть от зарегистрированных'),
      'options' => $fields,
      )));

    return $form;
  }

  private static function getTypes()
  {
    $list = array();

    foreach (TypeNode::getSchema() as $k => $v)
      if ('comment' != $k)
        $list[$k] = $v['title'];

    asort($list);

    return $list;
  }

  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $options['status'] = $ctx->get('status', 'form');
    $options['user'] = mcms::user()->id;

    if (null === ($options['doc'] = $ctx->document->id))
      throw new WidgetHaltedException();

    if (empty($this->allowed_types) or !in_array($ctx->document->class, $this->allowed_types)) {
      if (bebop_is_debugger())
        mcms::log('comment', $this->getInstanceName() .': widget halted: '
          .'type not allowed: '. $ctx->document->class);
      throw new WidgetHaltedException();
    }

    return $options;
  }

  public function onGet(array $options)
  {
    return array('html' => parent::formRender('comment-new'));
  }

  protected function getNewForm($strip = true)
  {
    $form = Node::create('comment')->formGet(/* simple = */ true);

    $form->addControl(new HiddenControl(array(
      'value' => 'comment_node',
      'default' => $this->options['doc'],
      )));

    if ($strip) {
      if (mcms::user()->id)
        $skip = $this->hide_user;
      else
        $skip = $this->hide_anon;

      foreach ($skip as $k)
        $form->replaceControl('node_content_'. $k, null);
    }

    return $form;
  }

  public function formGet($id)
  {
    $form = null;

    switch ($id) {
    case 'comment-new':
      $form = $this->getNewForm();
      break;
    }

    $form->captcha = true;

    return $form;
  }

  public function formGetData()
  {
    return array();
  }

  private function sendNotifications(Node $c)
  {
    if (mcms::ismodule('rating')) {
      if ($uid = mcms::user()->id)
        $me = Node::load(array('class' => 'user', 'id' => $uid));
      else
        $me = null;

      try {
        $uids = mcms::db()->getResultsV("uid", "SELECT DISTINCT `uid` FROM `node__rating` WHERE `nid` = :nid AND `rate` > 0 AND `uid` > 0 AND `uid` <> :uid", array(
          ':nid' => $this->options['doc'],
          ':uid' => $me ? $me->id : -1,
          ));
      } catch (PDOException $e) {
        $uids = null;
      }

      if (!empty($uids)) {
        $prefix = 'http://'. $_SERVER['HTTP_HOST'] .'/';

        if (null !== $me)
          $message = '<p>'. t('Пользователь <a href=\'@profile\'>%user</a> только что прокомментировал тему «%title»:', array(
            '@profile' => $prefix . 'user/'. $me->id .'/',
            '%user' => $me->name,
            '%title' => $this->ctx->document->name,
            )) .'</p>';
        else
          $message = '<p>'. t('Пользователь %user только что прокомментировал тему «%title»:', array(
            '%user' => $c->name,
            '%title' => $this->ctx->document->name,
            )) .'</p>';

        $message .= t('<blockquote>%comment</blockquote>', array(
          '%comment' => $c->body,
          ));

        $message .= '<p>'. t('Можно <a href=\'@reply\'>ответить</a> или <a href=\'@silence\'>отписаться от комментариев</a>.', array(
          '@reply' => $prefix .'node/'. $this->ctx->document->id .'/',
          '@silence' => $prefix .'/node/'. $this->ctx->document->id .'/?'. $this->getInstanceName() .'.silence=1',
          )) .'</p>';

        if (null !== $me)
          $message .= '<p>'. t('Чтобы отправить пользователю личное сообщение, просто ответьте на это письмо.') .'</p>';

        $headers = array(
          'reply-to' => $me ? $me->email : null,
          );

        $emails = array();

        foreach (Node::find(array('class' => 'user', 'id' => $uids)) as $user)
          mcms::mail(null, $user, t('Новый комментарий на %site', array('%site' => $_SERVER['HTTP_HOST'])), $message, null, $headers);
      }
    }
  }

  public function formHookConfigSaved()
  {
    CommentWidget::checkTypes();
  }
};
