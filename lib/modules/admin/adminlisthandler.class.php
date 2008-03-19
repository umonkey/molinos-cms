<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminListHandler
{
  protected $ctx;

  public $types;
  public $deleted;
  public $published;
  public $columns;
  public $title;
  public $actions;

  public function __construct(RequestContext $ctx)
  {
    $this->ctx = $ctx;

    if (null !== ($tmp = $ctx->get('deleted')))
      $this->deleted = $tmp;

    if (null !== ($tmp = $ctx->get('published')))
      $this->published = $tmp;

    if (null !== ($tmp = $ctx->get('type')))
      $this->types = explode(',', $tmp);

    $this->columns = explode(',', $ctx->get('columns', 'name'));

    $this->title = t('Список документов');
  }

  protected function getData()
  {
    $filter = array();

    if (null !== $this->deleted)
      $filter['deleted'] = $this->deleted;

    if (null !== $this->published)
      $filter['published'] = $this->published;

    if (null !== $this->types)
      $filter['class'] = $this->types;
    else
      $filter['-class'] = array('domain', 'widget', 'user', 'group', 'type', 'file');

    return Node::find($filter, $this->ctx->get('limit'), $this->ctx->get('offset'));
  }

  public function getHTML($preset = null)
  {
    $this->setUp($preset);

    $output = '<h2>'. $this->title .'</h2>';
    $output .= $this->getSearchForm();

    $form = new Form(array(
      'action' => '/nodeapi.rpc?action=mass&destination='. urlencode($_SERVER['REQUEST_URI']),
      ));
    $form->addControl(new AdminUINodeActions(array(
      'actions' => $this->actions,
      )));
    $form->addControl(new AdminUIList(array(
      'columns' => $this->columns,
      )));
    $form->addControl(new AdminUINodeActions(array(
      'actions' => $this->actions,
      )));

    $output .= $form->getHTML(array(
      'nodes' => $this->getData(),
      ));

    return $output;
  }

  protected function setUp($preset = null)
  {
    // Некоторые заготовки.
    if (null !== $preset) {
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
        $this->columns = array('name', 'class', 'uid', 'updated', 'created');
        $this->actions = array('undelete', 'erase');
        break;
      case 'groups':
        $this->types = array('group');
        $this->title = t('Список групп');
        $this->columns = array('name', 'title', 'description');
        $this->actions = array('delete', 'clone');
        break;
      case 'users':
        $this->types = array('user');
        $this->title = t('Список пользователей');
        $this->columns = array('name', 'login', 'email', 'created');
        $this->actions = array('delete', 'clone');
        break;
      case 'files':
        $this->types = array('file');
        $this->title = t('Файловый архив');
        $this->columns = array('thumbnail', 'name', 'filename', 'filetype', 'filesize', 'created');
        break;
      case 'schema':
        $this->types = array('type');
        $this->title = t('Типы документов');
        $this->columns = array('name', 'title', 'description', 'created');
        break;
      case 'widgets':
        $this->types = array('widget');
        $this->title = t('Список виджетов');
        $this->columns = array('name', 'title', 'classname', 'description', 'created');
        break;
      }
    }

    // Подбираем заголовок.
    if (count($this->types) == 1) {
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
      }
    }

    if (null === $this->actions)
      $this->actions = array(
        'delete',
        'publish',
        'unpublish',
        'clone',
        );
  }

  private function getSearchForm()
  {
    $form = new Form(array(
      'action' => $_SERVER['REQUEST_URI'],
      'method' => 'post',
      ));
    $form->addControl(new AdminUISearch(array(
      'q' => $this->ctx->get('search'),
      'type' => $this->types,
      )));
    return $form->getHTML(array());
  }
};
