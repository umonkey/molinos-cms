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

  protected $selectors;

  protected $limit;
  protected $page;

  protected $preset = null;

  // Кэшируем для исключения повторных вызовов.
  private $count = null;

  public function __construct(RequestContext $ctx)
  {
    $this->ctx = $ctx;

    $this->selectors = true;

    if (null !== ($tmp = $ctx->get('deleted')))
      $this->deleted = $tmp;

    if (null !== ($tmp = $ctx->get('published')))
      $this->published = $tmp;

    if (null !== ($tmp = $ctx->get('type')))
      $this->types = explode(',', $tmp);

    $this->columns = explode(',', $ctx->get('columns', 'name'));

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
        mcms::redirect("admin?mode=create&cgroup={$_GET['cgroup']}&dictionary=1&welcome=1&type={$this->types[0]}&destination=CURRENT");
      // mcms::redirect("admin?mode=create&cgroup={$_GET['cgroup']}&type={$this->types[0]}&destination=CURRENT");
    }

    $output = '<h2>'. $this->title .'</h2>';
    $output .= $this->getSearchForm();

    if (!empty($data)) {
      $form = new Form(array(
        'id' => 'nodelist-form',
        'action' => 'nodeapi.rpc?action=mass&destination=CURRENT',
        ));
      if (empty($_GET['picker']))
        $form->addControl(new AdminUINodeActionsControl(array(
          'actions' => $this->actions,
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
          )));
      $form->addControl(new PagerControl(array(
        'value' => '__pager',
        )));

      $output .= $form->getHTML(array(
        'nodes' => $data,
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
        $output .= mcms::html('p', t('Нет документов для отображения в этом списке, <a href=\'@addurl\'>приступить к добавлению</a>?', array('@addurl' => "admin?cgroup={$_GET['cgroup']}&mode=create&type={$this->types[0]}&destination=CURRENT")));
      else
        $output .= mcms::html('p', t('Нет документов для отображения в этом списке.'));
    }

    return $output;
  }

  private function getSearchForm()
  {
    $form = new Form(array(
      'action' => $_SERVER['REQUEST_URI'],
      'method' => 'post',
      ));
    $form->addControl(new AdminUISearchControl(array(
      'q' => $this->ctx->get('search'),
      'type' => $this->types,
      'value' => 'search',
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
        $this->zoomlink = "admin?cgroup=content&columns=name,class,uid,created&mode=list&search=uid%3ANODEID";
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
        $this->zoomlink = "admin?cgroup=content&columns=name,class,uid,created&mode=list&search=class%3ANODENAME";
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
          $tmp = TypeNode::getSchema($type);

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

      foreach (TypeNode::getSchema() as $k => $v) {
        if (empty($v['isdictionary']) and (empty($v['adminmodule']) or !mcms::ismodule($v['adminmodule'])) and !in_array($k, $itypes))
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

    return $filter;
  }

  protected function getData()
  {
    $result = array();
    $itypes = TypeNode::getInternal();

    foreach (Node::find($this->getNodeFilter(), $this->limit, ($this->page - 1) * $this->limit) as $node)
      $result[] = $node->getRaw();

    switch ($this->ctx->get('preset')) {
    case 'schema':
      $tmp = array();

      foreach ($result as $k => $v)
        if (!bebop_is_debugger() and in_array($v['name'], $itypes) or !empty($v['isdictionary']))
          unset($result[$k]);

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
          $result[$k]['#link'] = l("admin?cgroup=content&preset=dict&mode=list&type=". $v['name']);
        }
      }
      break;
    }

    return $result;
  }

  protected function getCount()
  {
    if (null === $this->pgcount) {
      $filter = $this->getNodeFilter();
      $this->pgcount = Node::count($filter);
    }

    return $this->pgcount;
  }
};
