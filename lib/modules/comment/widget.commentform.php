<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentFormWidget extends Widget
{
  private $options;
  protected $newcomment = null;

  public function __construct($name, array $data)
  {
    if (!array_key_exists('params', $data))
      $data['params'] = array(
        'moderate' => true,
        );

    parent::__construct($name, $data);
  }

  /**
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'class' => __CLASS__,
      'name' => 'Комментарий (добавление)',
      'description' => 'Позволяет пользователям оставлять комментарии.',
      );
  }

  public static function getConfigOptions(Context $ctx)
  {
    $fields = array();
    $schema = Schema::load($ctx->db, 'comment');
    foreach ($schema as $k => $v)
      $fields[$k] = $v->label . ' (' . $k . ')';

    return array(
      'moderated' => array(
        'type' => 'BoolControl',
        'label' => t('Премодерация'),
        'description' => t('Комментарии будут сохраняться, но на сайте они будут отображаться только после одобрения модератором.'),
        ),
      'allowed_types' => array(
        'type' => 'SetControl',
        'label' => t('Комментируемые документы'),
        'options' => self::getTypes(),
        ),
      'hide_anon' => array(
        'type' => 'SetControl',
        'label' => t('Скрыть от анонимных'),
        'options' => $fields,
        ),
      'hide_user' => array(
        'type' => 'SetControl',
        'label' => t('Скрыть от зарегистрированных'),
        'options' => $fields,
        ),
      );
  }

  private static function getTypes()
  {
    $list = array();

    foreach (Node::getSortedList('type', 'title', 'name') as $k => $v) {
      if ('comment' != $k)
        $list[$k] = $v;
    }

    return $list;
  }

  protected function getRequestOptions(Context $ctx, array $params)
  {
    $options = parent::getRequestOptions($ctx, $params);

    $options['#cache'] = false;
    $options['status'] = $this->get('status', 'form');
    $options['user'] = $ctx->user->id;

    if (null === ($options['doc'] = $params['document']))
      return $this->halt();

    return $this->options = $options;
  }

  public function onGet(array $options)
  {
    $form = $this->getNewForm();
    $form->captcha = true;

    return $form->getXML(Node::create('comment'));
  }

  protected function getNewForm($strip = true)
  {
    $form = Node::create('comment')->formGet();

    $form->addControl(new HiddenControl(array(
      'value' => 'comment_node',
      'default' => $this->options['doc'],
      )));

    if ($strip) {
      if ($this->ctx->user->id)
        $skip = $this->hide_user;
      else
        $skip = $this->hide_anon;

      foreach ((array)$skip as $k)
        $form->replaceControl($k, null);
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
    if (class_exists('RatingRPC')) {
      if ($uid = $this->ctx->user->id)
        $me = Node::load(array('class' => 'user', 'id' => $uid));
      else
        $me = null;

      try {
        $uids = $this->ctx->db->getResultsV("uid", "SELECT DISTINCT `uid` FROM `node__rating` WHERE `nid` = :nid AND `rate` > 0 AND `uid` > 0 AND `uid` <> :uid", array(
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

        foreach (Node::find($c->getDB(), array('class' => 'user', 'id' => $uids)) as $user)
          mcms::mail(null, $user, t('Новый комментарий на %site', array('%site' => $_SERVER['HTTP_HOST'])), $message, null, $headers);
      }
    }
  }
};
