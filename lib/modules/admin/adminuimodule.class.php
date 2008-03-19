<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminUIModule implements iAdminUI
{
  public static function onGet(RequestContext $ctx)
  {
    $result = array();

    if (null === ($module = $ctx->get('module')))
      $result['content'] = self::onGetInternal($ctx);

    elseif (!count($classes = mcms::getImplementors('iAdminUI', $module)))
      throw new PageNotFoundException();

    else
      $result['content'] = call_user_func_array(array($classes[0], 'onGet'), array($ctx));

    $result['dashboard'] = self::getDashboardIcons();

    $output = bebop_render_object('page', 'admin', 'admin', $result);

    header('Content-Type: text/html; charset=utf-8');
    die($output);
  }

  private static function onGetInternal(RequestContext $ctx)
  {
    switch ($mode = $ctx->get('mode')) {
    case 'list':
    case 'edit':
    case 'create':
      $method = 'onGet'. ucfirst(strtolower($mode));
      return call_user_func_array(array('AdminUIModule', $method), array($ctx));
    default:
      throw new PageNotFoundException();
    }
  }

  private static function onGetList(RequestContext $ctx)
  {
    $actions = self::onGetListActions($ctx);

    $output = '<h2>'. self::onGetListTitle($ctx) .'</h2>';
    $output .= self::getSearchForm($ctx);

    $form = new Form(array(
      'action' => '/nodeapi.rpc?action=mass&destination='. urlencode($_SERVER['REQUEST_URI']),
      ));
    $form->addControl(new AdminUINodeActions(array(
      'actions' => $actions,
      )));
    $form->addControl(new AdminUIList(array(
      'columns' => explode(',', $ctx->get('columns', 'name')),
      )));
    $form->addControl(new AdminUINodeActions(array(
      'actions' => $actions,
      )));

    $output .= $form->getHTML(array(
      'nodes' => self::onGetListNodes($ctx),
      ));

    return $output;
  }

  private static function onGetListNodes(RequestContext $ctx)
  {
    $limit = null;
    $offset = 0;
    $filter = array();

    if ($ctx->get('deleted'))
      $filter['deleted'] = 1;
    else {
      if (null !== ($class = $ctx->get('type')))
        $filter['class'] = explode(',', $class);
      else
        $filter['-class'] = array('domain', 'widget', 'user', 'group', 'type', 'file');
    }

    if ($ctx->get('published') === '0')
      $filter['published'] = 0;

    foreach (explode(',', $ctx->get('sort', '-id')) as $field) {
      if (substr($field, 0, 1) == '-') {
        $mode = 'desc';
        $field = substr($field, 1);
      } else {
        $mode = 'asc';
      }

      $filter['#sort'][$field] = $mode;
    }

    return Node::find($filter, $limit, $offset);
  }

  private static function onGetListActions(RequestContext $ctx)
  {
    switch ($ctx->get('type')) {
    case 'user':
      return array(
        'delete',
        'enable',
        'disable',
        'clone',
        );
    case 'group':
      return array(
        'delete',
        'clone',
        );
    default:
      return array(
        'delete',
        'publish',
        'unpublish',
        'clone',
        );
    }
  }

  private function onGetListTitle(RequestContext $ctx)
  {
    switch ($type = $ctx->get('type')) {
    case 'widget':
      return t('Список виджетов');
    case 'type':
      return t('Список типов документов');
    case 'files':
      return t('Список файлов');
    case 'user':
      return t('Список пользователей');
    case 'group':
      return t('Список групп');
    default:
      if (null === $type) {
        if ('0' === $ctx->get('published'))
          return t('Документы в модерации');
        elseif ($ctx->get('deleted'))
          return t('Удалённые документы');
      }

      return t('Список документов');
    }
  }

  private static function getSearchForm(RequestContext $ctx)
  {
    $form = new Form(array(
      'action' => $_SERVER['REQUEST_URI'],
      'method' => 'post',
      ));
    $form->addControl(new AdminUISearch(array(
      'q' => $ctx->get('search'),
      'type' => $ctx->get('type'),
      )));
    return $form->getHTML(array());
  }

  private static function getDashboardIcons()
  {
    $result = array();

    $classes = mcms::getClassMap();
    $rootlen = strlen(dirname(dirname(dirname(dirname(__FILE__)))));

    foreach (mcms::getImplementors('iDashboard') as $class) {
      $icons = call_user_func(array($class, 'getDashboardIcons'));

      if (is_array($icons) and !empty($icons))
        foreach ($icons as $icon) {
          if (!empty($icon['img'])) {
            $classpath = dirname($classes[strtolower($class)]);
            $icon['img'] = substr($classpath, $rootlen) .'/'. $icon['img'];
          }

          $result[$icon['group']][] = $icon;
        }
    }

    return $result;
  }

  private static function onGetEdit(RequestContext $ctx)
  {
    if (null === ($nid = $ctx->get('id')))
      throw new PageNotFoundException();

    $node = Node::load(array('id' => $nid));

    $form = $node->formGet();
    $form->addClass('tabbed');

    return $form->getHTML($node->formGetData());
  }

  private static function onGetCreate(RequestContext $ctx)
  {
    if (null !== $ctx->get('type')) {
      $node = Node::create($ctx->get('type'));

      $form = $node->formGet(false);
      return $form->getHTML($node->formGetData());
    }

    $types = Node::find(array('class' => 'type', '#sort' => array('type.title' => 'asc')));

    $output = '<dl>';

    foreach ($types as $type) {
      $output .= '<dt>';
      $output .= mcms::html('a', array(
        'href' => "/admin/?mode=create&type={$type->name}&destination=". $_GET['destination'],
        ), $type->title);
      $output .= '</dt>';

      if (isset($type->description))
        $output .= '<dd>'. $type->description .'</dd>';
    }

    $output .= '</dl>';

    return '<h2>Какой документ вы хотите создать?</h2>'. $output;
  }
};
