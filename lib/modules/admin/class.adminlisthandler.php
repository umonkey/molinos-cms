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
        mcms::redirect("?q=admin&mode=create&cgroup={$_GET['cgroup']}&dictionary=1&welcome=1&type={$this->types[0]}&destination=CURRENT");
      // mcms::redirect("?q=admin&mode=create&cgroup={$_GET['cgroup']}&type={$this->types[0]}&destination=CURRENT");
    }

    $output = '<h2>'. $this->title .'</h2>';

    if (!$this->hidesearch)
      $output .= $this->getSearchForm();

    if (!empty($data)) {
      $form = new Form(array(
        'id' => 'nodelist-form',
        'action' => '?q=nodeapi.rpc&action=mass&destination=CURRENT',
        ));
      if (empty($_GET['picker']))
        $form->addControl(new AdminUINodeActionsControl(array(
          'actions' => $this->actions,
          'addlink' => $this->addlink,
          )));

      $form->addControl(new AdminUIListControl(array(
        'columns' => $this->columns,
        'picker' => $this->ctx->get('picker'),
        'selectors' => $this->selectors,
        'noedit' => $this->noedit,
        'columntitles' => $this->columntitles,
        'linkfield' => $this->linkfield,
        'zoomlink' => $this->zoomlink,
        )));

      if (empty($_GET['picker']))
        $form->addControl(new AdminUINodeActionsControl(array(
          'actions' => $this->actions,
          'addlink' => $this->addlink,
          )));
      $form->addControl(new PagerControl(array(
        'value' => '__pager',
        )));

      $output .= $form->getHTML(array(
        'nodes' => $data,
        'preset' => $preset,
        '__pager' => $this->getPager(),
        ));
    }

    elseif (null !== $this->ctx->get('search')) {
      $tmp = bebop_split_url();
      $tmp['args']['search'] = null;

      $output .= '<p>'. t('Нет документов, удовлетворяющих запросу.  <a href=\'@url\'>Отмените поиск</a> или поищите что-нибудь другое.', array(
        '@url' => bebop_combine_url($tmp, false),
        )) .'</p>';
    }

    elseif (0 == $this->getCount()) {
      if (count($this->types) == 1)
        $output .= mcms::html('p', t('Нет документов для отображения в этом списке, <a href=\'@addurl\'>приступить к добавлению</a>?', array('@addurl' => "?q=admin&cgroup={$_GET['cgroup']}&mode=create&type={$this->types[0]}&destination=CURRENT")));
      else
        $output .= mcms::html('p', t('Нет документов для отображения в этом списке.'));
    }

    return $output;
  }

  private function getSearchForm()
  {
    $form = new Form(array(
      'action' => '?q=admin.rpc&action=search',
      ));
    $form->addControl(new HiddenControl(array(
      'value' => 'search_from',
      'default' => $_SERVER['REQUEST_URI'],
      )));
    $form->addControl(new AdminUISearchControl(array(
      'q' => $this->ctx->get('search'),
      'type' => $this->types,
      'value' => 'search_term',
      )));
    return $form->getHTML(array());
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
          $tmp = Node::create($type)->schema();

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

    if ($this->deleted)
      ;
    elseif (null !== $this->types)
      $filter['class'] = $this->types;
    else {
      $filter['class'] = array();
      $itypes = TypeNode::getInternal();

      foreach (Node::getSortedList('type') as $k => $v) {
        if (empty($v->isdictionary) and (empty($v->adminmodule) or !mcms::ismodule($v->adminmodule)) and !in_array($k, $itypes))
          $filter['class'][] = $k;
      }

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
    $filter['#cache'] = false;

    if ('pages' == $this->preset)
      $filter['parent_id'] = null;

    return $filter;
  }

  protected function getData()
  {
    if ('404' == $this->preset) {
      $data = array();

      foreach (mcms::db()->getResults("SELECT * FROM `node__fallback`") as $row) {
        $row['_links'] = array(
          'edit' => array(
            'href' => '?q=admin/content/edit/404/'. urlencode($row['old'])
              .'&destination=CURRENT',
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

    $result = array();
    $itypes = TypeNode::getInternal();

    foreach (Node::find($this->getNodeFilter(), $this->limit, ($this->page - 1) * $this->limit) as $node) {
      $tmp = $node->getRaw();
      $tmp['_links'] = $node->getActionLinks();
      $result[] = $tmp;
    }

    switch ($this->ctx->get('preset')) {
    case 'schema':
      $tmp = array();

      foreach ($result as $k => $v) {
        if (!bebop_is_debugger() and in_array($v['name'], $itypes) or !empty($v['isdictionary']))
          unset($result[$k]);

        if (!empty($v['adminmodule']) and !mcms::ismodule($v['adminmodule']))
          unset($result[$k]);
      }

      foreach ($result as $v) {
        if (!in_array($v['name'], $itypes)) {
          $tmp[] = $v;
        }
      }

      foreach ($result as $v) {
        if (in_array($v['name'], $itypes)) {
          $v['_protected'] = !bebop_is_debugger();
          $v['published'] = false;
          $tmp[] = $v;
        }
      }

      $result = $tmp;
      break;

    case 'dictlist':
      foreach ($result as $k => $v) {
        if (empty($v['isdictionary'])) {
          unset($result[$k]);
        } else {
          $result[$k]['#link'] = l("?q=admin&cgroup=content&preset=dict&mode=list&type=". $v['name']);
        }
      }
      break;

    case 'pages':
      foreach ($result as $k => $v)
        if (empty($v['redirect']))
          $result[$k]['#link'] = '?q=admin/structure/tree/pages/'. $v['id'];
        else
          $result[$k]['#nolink'] = true;
    }

    return $result;
  }

  protected function getCount()
  {
    if (null === $this->pgcount) {
      switch ($this->preset) {
      case '404':
        $this->pgcount = mcms::db()
          ->fetch("SELECT COUNT(*) FROM `node__fallback`");
        break;
      default:
        $filter = $this->getNodeFilter();
        $this->pgcount = Node::count($filter);
      }
    }

    return $this->pgcount;
  }
};
