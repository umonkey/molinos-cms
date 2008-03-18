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
      $method = 'onGet'. ucfirst(strtolower($mode));
      return call_user_func_array(array('AdminUIModule', $method), array($ctx));
    default:
      throw new PageNotFoundException();
    }
  }

  private static function onGetList(RequestContext $ctx)
  {
    $actions = self::onGetListActions($ctx);

    $output = self::getSearchForm($ctx);

    $form = new Form(array(
      'title' => t('Список документов'),
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

  private static function onGetEdit(RequestContext $ctx)
  {
    if (null === ($nid = $ctx->get('id')))
      throw new PageNotFoundException();

    $node = Node::load(array('id' => $nid));

    $form = $node->formGet();
    $form->addClass('tabbed');

    return $form->getHTML($node->formGetData());
  }

  private static function getSearchForm(RequestContext $ctx)
  {
  }

  private static function getDashboardIcons()
  {
    $result = array();

    foreach (mcms::getImplementors('iDashboard') as $class) {
      $icons = call_user_func(array($class, 'getDashboardIcons'));

      if (is_array($icons) and !empty($icons))
        foreach ($icons as $icon) {
          $result[$icon['group']][] = $icon;
        }
    }

    return $result;
  }
};
