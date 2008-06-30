<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentWidget extends Widget
{
  public function __construct(Node $node)
  {
    if (empty($node->config))
      $node->config = array(
        'perpage' => 10,
        'startwith' => 'last',
        );

    parent::__construct($node);
  }

  public static function checkTypes()
  {
    if (!Node::count(array('class' => 'type', 'name' => 'comment'))) {
      $type = Node::create('type', array(
        'name' => 'comment',
        'title' => t('Комментарий'),
        'fields' => array(
          'name' => array(
            'type' => 'TextLineControl',
            'label' => t('Ваше имя'),
            'required' => true,
            ),
          'body' => array(
            'type' => 'TextAreaControl',
            'label' => t('Сообщение'),
            ),
          'email' => array(
            'type' => 'EmailControl',
            'label' => t('E-mail'),
            'required' => true,
            ),
          'url' => array(
            'type' => 'URLControl',
            'label' => t('Домашняя страница'),
            ),
          'ip' => array(
            'type' => 'TextLineControl',
            'label' => t('IP адрес'),
            'hidden' => true,
            ),
          ),
        ));
      $type->save();

      $type->setAccess(array(
        'Visitors' => array('c', 'r'),
        'Content Managers' => array('r'),
        'Schema Managers' => array('r', 'u', 'd'),
        ));
    }
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Комментарии (просмотр)',
      'description' => 'Позволяет пользователям читать комментарии.',
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_startwith',
      'label' => t('По умолчанию показывать'),
      'required' => true,
      'options' => array(
        'first' => t('Первую страницу'),
        'last' => t('Последнюю страницу'),
        'form' => t('Форму добавления комментария'),
        'nothing' => t('Ссылку на отдельную страницу с комментариями'),
        ),
      )));

    $form->addControl(new NumberControl(array(
      'value' => 'config_perpage',
      'label' => t('Комментариев на странице'),
      'required' => true,
      'default' => 10,
      )));

    return $form;
  }

  public function formHookConfigData(array &$data)
  {
    // $data['xyz'] = 123;
  }

  public function formHookConfigSaved()
  {
    self::checkTypes();
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['status'] = $ctx->get('status');

    switch ($options['action'] = $ctx->get('action', 'list')) {
    case 'list':
      switch ($this->startwith) {
      case 'last':
        $options['default'] = 'last';
        if (null === ($options['doc'] = $ctx->document_id))
          throw new WidgetHaltedException();
        break;

      case 'first':
        $options['default'] = 1;
        if (null === ($options['doc'] = $ctx->document_id))
          throw new WidgetHaltedException();
        break;

      case 'tracker':
        $options['default'] = 1;
        $options['action'] = 'tracker';
        $options['doc'] = $ctx->document_id;

        if (null === $this->perpage)
          throw new WidgetHaltedException(t('Свежие комментарии не выведены, т.к. не указано количество комментариев на странице.'));

        break;
      }

      $options['page'] = $ctx->get('page', $options['default']);
      break;
    }

    return $this->options = $options;
  }

  private function listComments($nid, $page = 1)
  {
    $offset = ($page - 1) * $this->perpage;

    $sql = "SELECT `id` FROM `node` `n` "
      ."INNER JOIN `node__rel` `r` ON `r`.`nid` = `n`.`id` "
      ."WHERE `r`.`tid` = :id AND `n`.`published` = 1 "
      ."AND `n`.`deleted` = 0 AND `n`.`class` = 'comment' "
      ."ORDER BY `n`.`id` ASC LIMIT {$offset}, {$this->perpage}";

    $cids = mcms::db()->getResultsV("id", $sql, array(':id' => $nid));

    return $cids;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['action']), $options);
  }

  protected function onGetList(array $options)
  {
    $result = array();

    $pdo = mcms::db();

    $total = $pdo->getResult("SELECT COUNT(*) FROM `node` `n` "
      ."INNER JOIN `node__rel` `r` ON `r`.`nid` = `n`.`id` "
      ."WHERE `n`.`published` = 1 AND `n`.`deleted` = 0 "
      ."AND `n`.`class` = 'comment' AND `r`.`tid` = :id",
      array(':id' => $options['doc']));

    if ($total > $this->perpage)
      $result['pager'] = $this->getPager($total, $options['page'], $this->perpage, $options['default']);

    $cids = $this->listComments($options['doc'], empty($result['pager']['current']) ? 1 : $result['pager']['current']);

    $result['comments'] = self::fixNames(Node::find(array('class' => 'comment', 'id' => $cids, '#sort' => array('id' => 'asc'))));

    return $result;
  }

  protected function onGetTracker(array $options)
  {
    $result = array();
    $filter = array('class' => 'comment', '#sort' => array('id' => 'desc'));

    if (!empty($options['doc']) and is_numeric($options['doc']))
      $filter['tags'] = array($options['doc']);

    if (($count = Node::count($filter)) > $this->perpage)
      $result['pager'] = $this->getPager($count, $options['page'], $this->perpage);

    $page = empty($result['pager']['current']) ? 1 : $result['pager']['current'];
    $limit = $this->perpage;
    $offset = ($page - 1) * $limit;

    $result['comments'] = self::fixNames(Node::find($filter, $limit, $offset));

    if (!empty($result['comments'])) {
      $parents = array();
      $cids = join(', ', array_keys($result['comments']));

      $map = mcms::db()->getResultsKV("nid", "tid", "SELECT `r`.`nid`, `r`.`tid` FROM `node__rel` `r` WHERE `r`.`nid` IN ({$cids})");

      $nodes = Node::find(array('id' => array_unique($map)));

      foreach ($map as $k => $v)
        $result['comments'][$k]['node'] = $nodes[$v]->getRaw();
    }

    if (null !== $this->ctx->document_id)
      $result['root'] = $this->ctx->document->getRaw();

    return $result;
  }

  private static function fixNames(array $nodes)
  {
    $uids = array();

    foreach ($nodes as $k => $v)
      if (null !== $v->uid)
        $uids[] = $v->uid;

    $users = Node::find(array('class' => 'user', 'id' => array_unique($uids)));

    foreach ($nodes as $k => $v) {
      if (null !== $v->uid and array_key_exists($v->uid, $users))
        $v->name = $users[$v->uid]->name;
      $nodes[$k] = $v->getRaw();
    }

    return $nodes;
  }
};

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
    $options['user'] = mcms::user()->getUid();

    if (null === ($options['doc'] = $ctx->document_id))
      throw new WidgetHaltedException();

    return $this->options = $options;
  }

  public function onGet(array $options)
  {
    return array('html' => parent::formRender('comment-new'));
  }

  public function formGet($id)
  {
    $form = null;

    switch ($id) {
    case 'comment-new':
      $user = mcms::user();
      $schema = TypeNode::getSchema('comment');
      $hidden = $user->getUid() ? $this->hide_user : $this->hide_anon;

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
        if (!in_array($k, (array)$hidden)) {
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
      $result['comment_name'] = $user->getName();
      break;
    }

    return $result;
  }

  public function formProcess($id, array $data)
  {
    mcms::captchaCheck($data);

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
          switch (mcms_ctlname($v['type'])) {
          case 'AttachmentControl':
            break;
          case 'NodeLinkControl':
            if (!empty($v['values']) and is_string($v['values'])) {
              $parts = explode('.', $v['values'], 2);

              if (!count($tmp = Node::find($filter = array('class' => $parts[0], $v['values'] => $data[$key]), 1)))
                throw new ValidationError($k);

              $comment[$k] = key($tmp);
            }
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
        if (!empty($data[$key = 'comment_'. $k]) and 'AttachmentControl' == mcms_ctlname($v['type']) and !empty($data[$key]['tmp_name'])) {
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
      if ($uid = mcms::user()->getUid())
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
          bebop_mail(null, $user->email, t('Новый комментарий на %site', array('%site' => $_SERVER['HTTP_HOST'])), $message, null, $headers);
      }
    }
  }

  public function formHookConfigSaved()
  {
    CommentWidget::checkTypes();
  }
};
