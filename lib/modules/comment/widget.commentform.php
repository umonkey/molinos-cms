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
    $schema = TypeNode::getSchema('comment');

    foreach ($schema['fields'] as $k => $v)
      $fields[$k] = $v['label'];

    $form->addControl(new BoolControl(array(
      'value' => 'config_moderated',
      'label' => t('Премодерация'),
      'description' => t('Комментарии будут сохраняться, но на сайте они будут отображаться только после одобрения модератором.'),
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

  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['status'] = $ctx->get('status', 'form');
    $options['user'] = mcms::user()->id;

    if (null === ($options['doc'] = $ctx->document_id))
      throw new WidgetHaltedException();

    return $this->options = $options;
  }

  public function onGet(array $options)
  {
    return array('html' => parent::formRender('comment-new'));
  }

  protected function getNewForm($strip = true)
  {
    $user = mcms::user();
    $schema = TypeNode::getSchema('comment');
    $hidden = $user->id ? $this->hide_user : $this->hide_anon;

    $form = new Form(array(
      'title' => t('Добавить комментарий'),
      ));

    $welcome = null;

    switch ($this->options['status']) {
      case 'pending':
        $welcome = t('Ваш комментарий добавлен, но на сайте будет показан только после одобрения модератором.');
        break;
      case 'published':
        $welcome = t('Ваш комментарий добавлен.');
        break;
    }

    if (null !== $welcome)
      $form->addControl(new InfoControl(array(
        'text' => $welcome,
        )));

    foreach ($schema['fields'] as $k => $v) {
      if (!in_array($k, (array)$hidden) or !$strip) {
        $v['value'] = 'comment_'. $k;

        if (null !== ($ctl = Control::make($v)))
          $form->addControl($ctl);
      }
    }

    $form->addControl(new HiddenControl(array(
      'value' => 'comment_node',
      )));

    $form->addControl(new SubmitControl(array(
      'text' => t('Отправить'),
      )));

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

  public function formGetData($id)
  {
    $result = array();

    switch ($id) {
    case 'comment-new':
      $user = mcms::user();
      $result['comment_node'] = $this->options['doc'];
      $result['comment_name'] = $user->name;
      break;
    }

    return $result;
  }

  public function formProcess($id, array $data)
  {
    mcms::captchaCheck($data);

    $data = $this->ctx->post;

    switch ($id) {
    case 'comment-new':
      $user = mcms::user();
      $schema = TypeNode::getSchema('comment');

      // Добавляем IP адрес в исходные данные, если он описан в типе -- будет обработан.
      $data['comment_ip'] = $_SERVER['REMOTE_ADDR'];

      $comment = array();

      foreach ($schema['fields'] as $k => $v) {
        if (empty($data[$key = 'comment_'. $k])) {
          $comment[$k] = null;
        } else {
          switch ($v['type']) {
          case 'AttachmentControl':
            break;
          default:
            $comment[$k] = $data[$key];
          }
        }
      }

      $comment['published'] = !$this->moderated;

      if ($user->getUid())
        $comment['uid'] = $user->getUid();

      try {
        $doc = Node::load($this->options['doc']);

        if (!empty($doc))
          $docname = '«'. $doc->name .'»';
        else
          $docname = 'документ без названия';
      } catch (ObjectNotFoundException $e) {
        $docname = 'неизвестный документ';
      }

      $node = Node::create('comment', $comment);
      $node->name = t('%user комментирует %doc', array(
        '%user' => mcms::user()->name,
        '%doc' => $docname,
        ));
      $node->save();

      $node->linkAddParent($this->options['doc']);

      // Обрабатываем файлы.
      foreach ($schema['fields'] as $k => $v) {
        if (!empty($data[$key = 'comment_'. $k]) and 'AttachmentControl' == $v['type'] and !empty($data[$key]['tmp_name'])) {
          $file = Node::create('file');
          $file->import($data[$key], false);
          $file->linkAddParent($node->id, $k);
        }
      }

      $this->sendNotifications($node);

      // Сохраняем ссылку на новый комментарий, чтоб перегружаемые классы могли получить к нему доступ.
      $this->newcomment = $node;

      $url = mcms_url(array(
        'args' => array(
          $this->getInstanceName() => array(
            'status' => $this->moderated ? 'pending' : 'published',
            ),
          ),
        ));

      /*
      if (!$this->moderated)
        $url .= '#comment-new';
      */

      return $url;
    }
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
          '@reply' => $prefix .'node/'. $this->ctx->document_id .'/',
          '@silence' => $prefix .'/node/'. $this->ctx->document_id .'/?'. $this->getInstanceName() .'.silence=1',
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
