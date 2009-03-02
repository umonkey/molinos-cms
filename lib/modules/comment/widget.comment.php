<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentWidget extends Widget
{
  public function __construct($name, array $data)
  {
    if (!array_key_exists('params', $data))
      $data['params'] = array(
        'perpage' => 10,
        'startwith' => 'last',
        );

    parent::__construct($name, $data);
  }

  // FIXME: перетащить куда-нибудь.
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
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/CommentWidget',
      );
  }

  public static function getConfigOptions()
  {
    return array(
      'mode' => array(
        'type' => 'EnumControl',
        'label' => t('Режим работы'),
        'required' => true,
        'options' => array(
          'linked' => t('Комментарии к текущему объекту'),
          'last' => t('Последние комментарии'),
          ),
        ),
      'startwith' => array(
        'type' => 'EnumControl',
        'label' => t('По умолчанию показывать'),
        'required' => true,
        'options' => array(
          'first' => t('Первую страницу'),
          'last' => t('Последнюю страницу'),
          'form' => t('Форму добавления комментария'),
          'nothing' => t('Ссылку на отдельную страницу с комментариями'),
          ),
        ),
      'perpage' => array(
        'type' => 'NumberControl',
        'label' => t('Комментариев на странице'),
        'required' => true,
        'default' => 10,
        ),
      'deep' => array(
        'type' => 'BoolControl',
        'label' => t('Вытаскивать аватары пользователей'),
        'value' => 1,
        ),
      );
  }

  // Препроцессор параметров.
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $options['status'] = $this->get('status');

    $options['mode'] = $this->mode;
    if (empty($options['mode']))
      $options['mode'] = 'list';

    if ('linked' == $options['mode'])
      $options['mode'] = 'list';

    switch ($options['mode']) {
    case 'list':
      switch ($this->startwith) {
      case 'last':
        $options['default'] = 'last';
        if (null === ($options['doc'] = $ctx->document->id))
          throw new WidgetHaltedException();
        break;

      case 'first':
        $options['default'] = 1;
        if (null === ($options['doc'] = $ctx->document->id))
          throw new WidgetHaltedException();
        break;

      case 'tracker':
        $options['default'] = 1;
        $options['action'] = 'tracker';
        $options['doc'] = $ctx->document->id;

        if (null === $this->perpage)
          throw new WidgetHaltedException(t('Свежие комментарии не выведены, т.к. не указано количество комментариев на странице.'));

        break;
      }

      break;

    case 'last':
      break;
    }

    if (empty($options['default']))
      $options['default'] = 1;
    $options['page'] = $this->get('page', $options['default']);

    return $options;
  }

  private function listComments($nid, $page = 1)
  {
    $offset = ($page - 1) * $this->perpage;

    $sql = "SELECT `id` FROM `node` `n` "
      ."INNER JOIN `node__rel` `r` ON `r`.`nid` = `n`.`id` "
      ."WHERE `r`.`tid` = :id AND `n`.`published` = 1 "
      ."AND `n`.`deleted` = 0 AND `n`.`class` = 'comment' "
      ."ORDER BY `n`.`id` ASC LIMIT {$offset}, {$this->perpage}";

    $cids = $this->ctx->db->getResultsV("id", $sql, array(':id' => $nid));

    return $cids;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['mode']), $options);
  }

  protected function onGetList(array $options)
  {
    $output = '';
    $result = array();

    $pdo = $this->ctx->db;

    $total = $pdo->getResult("SELECT COUNT(*) FROM `node` `n` "
      ."INNER JOIN `node__rel` `r` ON `r`.`nid` = `n`.`id` "
      ."WHERE `n`.`published` = 1 AND `n`.`deleted` = 0 "
      ."AND `n`.`class` = 'comment' AND `r`.`tid` = :id",
      array(':id' => $options['doc']));

    $cids = $this->listComments($options['doc'], empty($options['page']) ? 1 : $options['page']);

    $nodes = Node::find($this->ctx->db, array(
      'class' => 'comment',
      'id' => $cids,
      '#sort' => 'id',
      ));

    $tmp = null;
    foreach ($nodes as $node)
      $tmp .= $node->getXML();
    if (!empty($tmp))
      $output .= html::em('comments', $tmp);

    if ($total > $this->perpage)
      $output .= $this->getPager($total, $options['page'], $this->perpage, $options['default']);

    return $output;
  }

  protected function onGetLast(array $options)
  {
    $result = array();
    $filter = array(
      'class' => 'comment',
      'published' => 1,
      '#sort' => '-id',
      );

    if (($count = Node::count($this->ctx->db, $filter)) > $this->perpage)
      $result['pager'] = $this->getPager($count, $options['page'], $this->perpage);

    $page = empty($result['pager']['current']) ? 1 : $result['pager']['current'];
    $limit = $this->perpage;
    $offset = ($page - 1) * $limit;

    $result['comments'] = self::fixNames(Node::find($this->ctx->db, $filter, $limit, $offset));

    if (!empty($result['comments'])) {
      $parents = array();
      $cids = join(', ', array_keys($result['comments']));

      $map = $this->ctx->db->getResultsKV("nid", "tid", "SELECT `r`.`nid` as `nid`, `r`.`tid` as `tid` FROM `node__rel` `r` WHERE `r`.`nid` IN ({$cids})");

      $nodes = Node::find($this->ctx->db, array('id' => array_unique($map)));

      foreach ($map as $k => $v)
        $result['comments'][$k]['node'] = $nodes[$v]->getRaw();
    }

    if (null !== $this->ctx->document->id)
      $result['root'] = $this->ctx->document->getRaw();

    return $result;
  }

  private static function fixNames(array $nodes)
  {
    $uids = array();

    foreach ($nodes as $k => $v) {
      if (null !== $v->uid and !is_object($v->uid))
        $uids[] = $v->uid;
      if (null !== $v->node)
        $uids[] = $v->node;
    }

    if (empty($uids))
      $users = array();
    else
      $users = Node::find(context::last()->db, array(
        'id' => array_unique($uids),
        ));

    foreach ($nodes as $k => $v) {
      if (null !== $v->uid and !is_object($v->uid) and array_key_exists($v->uid, $users))
        $v->uid = $users[$v->uid];
      if (null !== $v->node and array_key_exists($v->node, $users))
        $v->node = $users[$v->node];
      $nodes[$k] = $v->getRaw();
    }

    return $nodes;
  }
};
