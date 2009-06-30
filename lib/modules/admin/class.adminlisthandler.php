<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminListHandler implements iAdminList
{
  protected $ctx;

  public $types;
  public $deleted;
  public $published;
  public $title;
  public $actions;
  public $linkfield;
  protected $addlink;

  protected $selectors;

  protected $limit;
  protected $page;

  protected $preset = null;
  protected $hidesearch = false;
  protected $nopermcheck = false;

  // Кэшируем для исключения повторных вызовов.
  private $count = null;

  public function __construct(Context $ctx, $type = null)
  {
    $this->ctx = $ctx;

    if (null !== ($tmp = $ctx->get('deleted')))
      $this->deleted = $tmp;

    if (null !== ($tmp = $ctx->get('published')))
      $this->published = $tmp;

    if (null !== $type)
      $this->types = explode(',', $type);
    elseif (null !== ($tmp = $ctx->get('type')))
      $this->types = explode(',', $tmp);

    $this->title = t('Список документов');

    $this->limit = $ctx->get('limit', 10);
    $this->page = $ctx->get('page', 1);
    $this->noedit = false;
    $this->pgcount = null;
  }

  public function getHTML($preset = null, array $options = array())
  {
    $this->setUp($preset);

    $data = $this->getData();

    $output = $data;
    $output .= $this->getPager();

    if ($raw = !empty($options['#raw']))
      unset($options['#raw']);

    $options = array_merge(array(
      'name' => 'list',
      'title' => $this->title,
      'preset' => $preset ? $preset : 'default',
      'search' => $this->hidesearch ? null : 'yes',
      'type' => $this->getType(),
      'addlink' => $this->addlink,
      'author' => $this->ctx->get('author'),
      ), $options);

    $output = html::em('content', $options, $output);

    if ($raw)
      return $output;

    $page = new AdminPage($output);
    return $page->getResponse($this->ctx);
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
      'from' => MCMS_REQUEST_URI,
      'q' => $this->ctx->get('search'),
      ));

    return $output;
  }

  private function getPager()
  {
    return mcms::pager($this->getCount(), $this->page, $this->limit);
  }

  protected function setUp($preset = null)
  {
    $this->deleted = 0;

    unset($this->title);
    // Некоторые заготовки.
    if (null !== ($this->preset = $preset)) {
      switch ($preset) {
      case 'drafts':
        $this->published = 0;
        $this->title = t('Документы в модерации');
        $this->actions = array('publish', 'delete');
        break;
      case 'trash':
        $this->deleted = 1;
        $this->title = t('Удалённые документы');
        $this->actions = array('undelete', 'erase');
        break;
      case 'groups':
        $this->types = array('group');
        $this->title = t('Список групп');
        $this->limit = null;
        $this->actions = array('delete');
        $this->selectors = array(
          'all' => 'все',
          'none' => 'ни одной',
          );
        break;
      case 'users':
        $this->types = array('user');
        $this->title = t('Список пользователей');
        $this->selectors = array(
          'all' => 'всех',
          'none' => 'ни одного',
          'published' => 'активных',
          'unpublished' => 'заблокированных',
          );
        break;
      case 'files':
        $this->types = array('file');
        $this->title = t('Файловый архив');
        $this->actions = array('publish', 'unpublish', 'delete');
        break;
      case 'comments':
        $this->types = array('comment');
        $this->title = t('Список комментариев');
        $this->sort = '-id';
        break;
      case '404':
        $this->title = t('Страницы, которые не были найдены');
        break;
      case 'fields':
        $this->types = array('field');
        break;
      case 'pages':
        $this->types = array('domain');
        $this->title = t('Домены');
        $this->hidesearch = true;
        $this->addlink = 'admin/create/domain'
          .'&destination=' . urlencode(MCMS_REQUEST_URI);
        $this->sort = 'name';
        $this->limit = null;
        break;
      }
    }

    // Подбираем заголовок.
    if (!isset($this->title) and count($this->types) == 1) {
      switch ($type = $this->types[0]) {
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
        case 'field':
          $this->title = t('Список полей');
          $this->limit = null;
          break;
        default:
          $tmp = Schema::load($this->ctx->db, $type);

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
      $filter['-class'] = ('trash' != $this->preset)
        ? TypeNode::getInternal()
        : array();
      $filter['#public'] = true;
    }

    if (!empty($this->sort))
      $filter['#sort'] = $this->sort;
    else
      $filter['#sort'] = '-id';

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

    if ('pages' == $this->preset)
      $filter['parent_id'] = null;

    if (array_key_exists('class', $filter) and empty($filter['class']))
      unset($filter['class']);

    $filter['deleted'] = !empty($this->deleted);

    if ($tmp = Context::last()->get('author'))
      $filter['uid'] = $tmp;

    // $q = new Query($filter); mcms::debug($q, $q->getSelect());

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
            ), html::plain($row['new']));

        if (!empty($row['ref'])) {
          $url = new url($row['ref']);
          if (0 === strpos($name = $url->host, 'www.'))
            $name = substr($name, 4);
          $row['ref'] = html::link($row['ref'], $name);
        }

        $data[] = $row;
      }

      return $data;
    }

    $result = '';

    $filter = $this->getNodeFilter();

    if (null !== $this->limit) {
      $filter['#limit'] = $this->limit;
      $filter['#offset'] = ($this->page - 1) * $this->limit;
    }

    $result = Node::findXML($filter);

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
        $this->pgcount = Node::count($filter, $this->ctx->db);
      }
    }

    return $this->pgcount;
  }

  protected function filterImmutable(array $types)
  {
    $user = Context::last()->user;

    $result = array_intersect($types,
      $user->getAccess(ACL::CREATE | ACL::UPDATE | ACL::DELETE | ACL::PUBLISH)
      );

    return $result;
  }
};
