<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminListHandler
{
  protected $ctx;

  public $types;
  public $deleted;
  public $published;
  public $columns;
  public $columntitles;
  public $title;
  public $actions;
  public $linkfield;
  public $zoomlink;
  protected $addlink;

  protected $selectors;

  protected $limit;
  protected $page;

  protected $preset = null;
  protected $hidesearch = false;

  // Кэшируем для исключения повторных вызовов.
  private $count = null;

  public function __construct(Context $ctx)
  {
    $this->ctx = $ctx;

    $this->selectors = true;

    if (null !== ($tmp = $ctx->get('deleted')))
      $this->deleted = $tmp;

    if (null !== ($tmp = $ctx->get('published')))
      $this->published = $tmp;

    if (null !== ($tmp = $ctx->get('type')))
      $this->types = explode(',', $tmp);

    $this->columns = explode(',', $ctx->get('columns', 'name,class,uid,created'));

    $this->title = t('Список документов');

    $this->limit = $ctx->get('limit', 10);
    $this->page = $ctx->get('page', 1);
    $this->noedit = false;
    $this->pgcount = null;
  }

  public function getHTML($preset = null)
  {
    $this->setUp($preset);

    $data = $this->getData();

    if (empty($data) and count($this->types) == 1 and null === $this->ctx->get('search')) {
      // Добавление справочника.
      if ('dictlist' == $this->ctx->get('preset'))
        $this->ctx->redirect("?q=admin&mode=create&cgroup={$_GET['cgroup']}&dictionary=1&welcome=1&type={$this->types[0]}&destination=CURRENT");
    }

    $output = self::getNodeActions(array(), $this->actions);

    $output .= $data;

    return html::em('list', array(
      'title' => $this->title,
      'preset' => $preset ? $preset : 'default',
      'search' => $this->hidesearch ? null : 'yes',
      'type' => $this->getType(),
      ), $output);
  }

  private function getType()
  {
    return empty($this->types)
      ? null
      : $this->types[0];
  }

  private function getSearchForm()
  {
    $output = html::em('form', array(
      'name' => 'search',
      'action' => '?q=admin.rpc&action=search',
      'from' => $_SERVER['REQUEST_URI'],
      'q' => $this->ctx->get('search'),
      ));

    return $output;
  }

  public static function getNodeActions(array $sel, array $act)
  {
    $selectors = array(
      'all' => 'все',
      'none' => 'ни одного',
      'published' => 'опубликованные',
      'unpublished' => 'скрытые',
      );

    $actions = array(
      'publish' => t('опубликовать'),
      'unpublish' => t('скрыть'),
      'delete' => t('удалить'),
      'clone' => t('клонировать'),
      'undelete' => t('восстановить'),
      'erase' => t('удалить окончательно'),
      'reindex' => t('индексировать'),
      );

    $output = '';

    foreach ($selectors as $k => $v)
      $output .= html::em('selector', array(
        'name' => $k,
        'title' => $v,
        ));

    foreach ($act as $action)
      $output .= html::em('action', array(
        'name' => $action,
        'title' => array_key_exists($action, $actions)
          ? $actions[$action]
          : $action,
        ));

    return html::em('massctl', $output);
  }

  private function getPager()
  {
    return mcms::pager($this->getCount(), $this->page, $this->limit);
  }

  protected function setUp($preset = null)
  {
    unset($this->title);
    // Некоторые заготовки.
    if (null !== ($this->preset = $preset)) {
      switch ($preset) {
      case 'drafts':
        $this->published = 0;
        $this->title = t('Документы в модерации');
        $this->columns = array('name', 'class', 'uid', 'updated', 'created');
        $this->actions = array('publish', 'delete');
        break;
      case 'trash':
        $this->deleted = 1;
        $this->title = t('Удалённые документы');
        $this->columns = array('created', 'name', 'class', 'uid', 'updated');
        $this->actions = array('undelete', 'erase');
        break;
      case 'groups':
        $this->types = array('group');
        $this->title = t('Список групп');
        $this->columns = array('name', 'description', 'created');
        $this->limit = null;
        $this->page = 1;
        $this->sort = array('name');
        break;
      case 'users':
        $this->types = array('user');
        $this->title = t('Список пользователей');
        $this->columns = array('name', 'fullname', 'created');
        $this->columntitles = array(
          'name' => 'Идентификатор',
          'fullname' => 'Полное имя',
          'created' => 'Зарегистрирован',
          );
        $this->sort = array('name');
        $this->zoomlink = "?q=admin&cgroup=content&columns=name,class,uid,created&mode=list&search=uid%3ANODEID";
        break;
      case 'files':
        $this->types = array('file');
        $this->title = t('Файловый архив');
        $this->columns = array('thumbnail', 'name', 'filename', 'filetype', 'filesize', 'uid', 'created');
        break;
      case 'schema':
        $this->types = array('type');
        $this->actions = array('delete', 'publish', 'unpublish', 'clone', 'reindex');
        $this->title = t('Типы документов');
        $this->columns = array('name', 'title', 'description', 'created');
        $this->limit = null;
        $this->page = 1;
        $this->sort = array('name');
        $this->zoomlink = "?q=admin&cgroup=content&columns=name,class,uid,created&mode=list&search=class%3ANODENAME";
        break;
      case 'widgets':
        $this->types = array('widget');
        $this->title = t('Список виджетов');
        $this->columns = array('name', 'title', 'classname', 'description', 'created');
        $this->limit = null;
        $this->page = 1;
        $this->sort = array('name');
        break;
      case 'comments':
        $this->types = array('comment');
        $this->title = t('Список комментариев');
        $this->columns = array('uid', 'text', 'created');
        $this->sort = array('-id');
        break;
      case 'dictlist':
        $this->title = t('Справочники');
        $this->types = array('type');
        $this->columns = array('title', 'name', 'uid', 'created');
        $this->columntitles = array(
          'title' => 'Название',
          'name' => 'Код',
          'uid' => 'Автор',
          'created' => 'Дата создания',
          );
        $this->linkfield = 'title';
        $this->sort = array('name');
        $this->limit = null;
        $this->page = 1;
        break;
      case 'dict':
        $this->columns = array('name', 'description', 'created');
        $this->columntitles = array(
          'name' => 'Заголовок',
          'description' => 'Описание',
          'created' => 'Добавлено',
          );
        $this->sort = array('name');
        break;
      case '404':
        $this->columns = array('old', 'new', 'ref');
        $this->columntitles = array(
          'old' => 'Запрошенная страница',
          'new' => 'Адрес перенаправления',
          'ref' => 'Источник',
          );
        $this->title = t('Страницы, которые не были найдены');
        break;
      case 'pages':
        $this->columns = array('name', 'title', 'redirect', 'theme');
        $this->columntitles = array(
          'name' => 'Домен',
          'title' => 'Заголовок',
          'redirect' => 'Редирект',
          'theme' => 'Шкура',
          );
        $this->types = array('domain');
        $this->title = t('Обрабатываемые домены');
        $this->hidesearch = true;
        $this->addlink = '?q=admin/structure/create&type=domain'
          .'&destination=CURRENT';
        $this->sort = array('name');
        $this->limit = null;
        break;
      }
    }

    // Подбираем заголовок.
    if (!isset($this->title) and count($this->types) == 1) {
      switch ($type = $this->types[0]) {
        case 'widget':
          $this->title = t('Список виджетов');
          break;
        case 'type':
          $this->title = t('Список типов документов');
          break;
        case 'files':
          $this->title = t('Список файлов');
          break;
        case 'user':
          $this->title = t('Список пользователей');
          break;
        case 'group':
          $this->title = t('Список групп');
          break;
        default:
          $tmp = Schema::load($type);

          if (!empty($tmp['isdictionary']))
            $this->title = t('Справочник «%name»', array('%name' => mb_strtolower($tmp['title'])));

          break;
      }
    }

    if (!isset($this->title))
      $this->title = t('Список документов');

    if (!isset($this->linkfield))
      $this->linkfield = 'name';

    if (null === $this->actions)
      $this->actions = array(
        'delete',
        'publish',
        'unpublish',
        'clone',
        );
  }

  protected function getNodeFilter()
  {
    $filter = array();

    if (null !== $this->deleted)
      $filter['deleted'] = $this->deleted;

    if (null !== $this->published)
      $filter['published'] = $this->published;

    if (null !== $this->types)
      $filter['class'] = $this->types;
    else {
      $filter['class'] = array();
      $itypes = TypeNode::getInternal();

      foreach (Node::find(array('class' => 'type', '#recurse' => 0)) as $n) {
        if (empty($n->isdictionary) and $this->haveModule($n->adminmodule) and !in_array($n->name, $itypes))
          $filter['class'][] = $n->name;
      }

      $filter['class'] = self::filterImmutable($filter['class']);

      if (empty($filter['class']))
        $filter['class'][] = null;
    }

    if (!empty($this->sort)) {
      foreach ($this->sort as $field) {
        if (substr($field, 0, 1) == '-') {
          $mode = 'desc';
          $field = substr($field, 1);
        } else {
          $mode = 'asc';
        }

        $filter['#sort'][$field] = $mode;
      }
    } else {
      $filter['#sort'] = array(
        'id' => 'desc',
        );
    }

    if (null !== ($tmp = $this->ctx->get('search')))
      $filter['#search'] = $tmp;

    if (count($tmp = explode(',', $this->ctx->get('filter'))) == 2) {
      switch ($tmp[0]) {
      case 'section':
        $filter['tags'] = $tmp[1];
        break;
      default:
        $filter[$tmp[0]] = $tmp[1];
        break;
      }
    }

    $filter['#permcheck'] = true;
    $filter['#recurse'] = 1;

    if ('pages' == $this->preset)
      $filter['parent_id'] = null;

    return $filter;
  }

  private function haveModule($moduleName)
  {
    if (empty($modulename))
      return true;

    if (!class_exists('modman'))
      return true;

    if (!modman::isInstalled($moduleName))
      return false;

    return true;
  }

  protected function getData()
  {
    if ('404' == $this->preset) {
      $data = array();

      $limit = $this->ctx->get('limit', 10);
      $offset = $limit * $this->ctx->get('page') - $limit;

      foreach ($this->ctx->db->getResults("SELECT * FROM `node__fallback` ORDER BY `old` LIMIT {$offset}, {$limit}") as $row) {
        $row['_links'] = array(
          'edit' => array(
            'href' => '?q=admin/content/edit/404'
              . '&subid=' . urlencode($row['old'])
              . '&destination=CURRENT',
            'title' => 'Изменить',
            'icon' => 'edit',
            ),
          'delete' => array(
            'href' => '?q=admin.rpc&action=404&mode=delete'
              .'&src='. urlencode($row['old']) .'&destination=CURRENT',
            'title' => 'Удалить',
            'icon' => 'delete',
            ),
          );

        if (!empty($row['new']))
          $row['new'] = html::em('a', array(
            'href' => '?q='. urlencode($row['new']),
            ), mcms_plain($row['new']));

        if (!empty($row['ref'])) {
          $url = new url($row['ref']);
          if (0 === strpos($name = $url->host, 'www.'))
            $name = substr($name, 4);
          $row['ref'] = l($row['ref'], $name);
        }

        $data[] = $row;
      }

      return $data;
    }

    $result = '';
    $itypes = TypeNode::getInternal();

    foreach ($nodes = Node::find($filter = $this->getNodeFilter(), $this->limit, ($this->page - 1) * $this->limit) as $node) {
      if ('dictlist' != $this->preset or !empty($node->isdictionary))
        $result .= $node->getXML('node');
    }

    return html::em('data', $result);
  }

  protected function getCount()
  {
    if (null === $this->pgcount) {
      switch ($this->preset) {
      case '404':
        $this->pgcount = $this->ctx->db->fetch("SELECT COUNT(*) FROM `node__fallback`");
        break;
      default:
        $filter = $this->getNodeFilter();
        $this->pgcount = Node::count($filter);
      }
    }

    return $this->pgcount;
  }

  protected function filterImmutable(array $types)
  {
    $user = mcms::user();

    $result = array_intersect($types,
      $user->getAccess('c') +
      $user->getAccess('u') +
      $user->getAccess('d') +
      $user->getAccess('p')
      );

    return $result;
  }
};
