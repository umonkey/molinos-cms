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
  public function getRequestOptions(Context $ctx)
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

    $result['comments'] = Node::find(array(
      'class' => 'comment',
      'id' => $cids,
      '#sort' => array('id' => 'asc'),
      '#raw' => true,
      ));

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

      $map = mcms::db()->getResultsKV("nid", "tid", "SELECT `r`.`nid` as `nid`, `r`.`tid` as `tid` FROM `node__rel` `r` WHERE `r`.`nid` IN ({$cids})");

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
