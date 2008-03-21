<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminUIModule implements iAdminUI, iDashboard
{
  public static function onGet(RequestContext $ctx)
  {
    if (!mcms::user()->hasGroup('Content Managers'))
      throw new ForbiddenException();

    $result = array();

    if (null === ($module = $ctx->get('module')))
      $result['content'] = self::onGetInternal($ctx);

    elseif (!count($classes = mcms::getImplementors('iAdminUI', $module)))
      throw new PageNotFoundException();

    else
      $result['content'] = call_user_func_array(array($classes[0], 'onGet'), array($ctx));

    $result['dashboard'] = self::getAllDashboardIcons();

    $output = bebop_render_object('page', 'admin', 'admin', $result);

    header('Content-Type: text/html; charset=utf-8');
    die($output);
  }

  private static function onGetInternal(RequestContext $ctx)
  {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $url = bebop_split_url();
      $url['args']['search'] = empty($_POST['search']) ? null : $_POST['search'];
      bebop_redirect($url);
    }

    switch ($mode = $ctx->get('mode', 'status')) {
    case 'list':
    case 'tree':
    case 'edit':
    case 'create':
    case 'logout':
    case 'status':
    case 'modules':
    case 'drafts':
    case 'trash':
      $method = 'onGet'. ucfirst(strtolower($mode));
      return call_user_func_array(array('AdminUIModule', $method), array($ctx));
    default:
      throw new PageNotFoundException();
    }
  }

  private static function onGetList(RequestContext $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML($ctx->get('preset'));
  }

  private static function onGetTree(RequestContext $ctx)
  {
    $tmp = new AdminTreeHandler($ctx);
    return $tmp->getHTML($ctx->get('preset'));
  }

  private static function onGetEdit(RequestContext $ctx)
  {
    if (null === ($nid = $ctx->get('id')))
      throw new PageNotFoundException();

    $node = Node::load(array('id' => $nid));

    $form = $node->formGet(false);
    $form->addClass('tabbed');

    return $form->getHTML($node->formGetData());
  }

  private static function onGetCreate(RequestContext $ctx)
  {
    if (null !== $ctx->get('type')) {
      $node = Node::create($type = $ctx->get('type'));

      $form = $node->formGet(false);
      $form->addClass('tabbed');
      $form->action = "/nodeapi.rpc?action=create&type={$type}&destination=". urlencode($_GET['destination']);

      return $form->getHTML($node->formGetData());
    }

    $types = Node::find(array(
      'class' => 'type',
      '-name' => array('type', 'widget', 'user', 'group', 'domain', 'tag', 'file'),
      '#sort' => array('type.title' => 'asc'),
      ));

    $output = '<dl>';

    foreach ($types as $type) {
      $output .= '<dt>';
      $output .= mcms::html('a', array(
        'href' => "/admin/?mode=create&type={$type->name}&destination=". urlencode($_GET['destination']),
        ), $type->title);
      $output .= '</dt>';

      if (isset($type->description))
        $output .= '<dd>'. $type->description .'</dd>';
    }

    $output .= '</dl>';

    return '<h2>Какой документ вы хотите создать?</h2>'. $output;
  }

  private static function onGetLogout(RequestContext $ctx)
  {
    mcms::user()->authorize();
    bebop_redirect($_GET['destination']);
  }

  private static function onGetStatus(RequestContext $ctx)
  {
    return 'Система в порядке.';
  }

  private static function onGetModules(RequestContext $ctx)
  {
    if ($ctx->get('action') == 'info' or $ctx->get('action') == 'config')
      return self::onGetModuleInfo($ctx->get('name'));

    $map = self::onGetModulesGroups();

    $output = '';

    foreach ($map as $group => $modules) {
      $output .= "<tr class='modgroup'><th colspan='4'>{$group}</th></tr>";

      foreach ($modules as $modname => $module) {
        $output .= '<tr>';
        $output .= "<td><input type='checkbox' name='selected[]' value='{$modname}' /></td>";
        $output .= "<td><a href='/admin/?mode=modules&action=info&name={$modname}'>{$modname}</a></td>";
        $output .= "<td>{$module['name']['ru']}</td>";

        if (!empty($module['implementors']['iModuleConfig']))
          $output .= "<td><a href='/admin/?mode=modules&action=config&name={$modname}'>настроить</a></td>";
        else
          $output .= "<td>&nbsp;</td>";

        $output .= '</tr>';
      }
    }

    $output = mcms::html('table', array(
      'class' => 'modlist',
      ), $output);

    $output .= mcms::html('input', array(
      'type' => 'submit',
      'value' => t('Сохранить'),
      ));

    return mcms::html('form', array(
      'method' => 'post',
      'action' => $_SERVER['REQUEST_URI'],
      ), $output);
  }

  private static function onGetModuleInfo($name)
  {
    $map = mcms::getModuleMap();

    if (empty($map['modules'][$name]))
      throw new PageNotFoundException();

    $module = $map['modules'][$name];

    $output = "<h2>Информация о модуле mod_{$name}</h2>";
    $output .= '<table class=\'modinfo\'>';
    $output .= '<tr><th>Описание:</th><td>'. $module['name']['ru'] .'</td></tr>';
    $output .= '<tr><th>Классы:</th><td>'. join(', ', $module['classes']) .'</td></tr>';

    if (!empty($module['interfaces']))
      $output .= '<tr><th>Интерфейсы:</th><td>'. join(', ', $module['interfaces']) .'</td></tr>';

    $output .= '<tr><th>Минимальная версия CMS:</th><td>'. $module['version'] .'</td></tr>';

    if (!empty($module['docurl']))
      $output .= '<tr><th>Документация:</th><td><a href=\''. $module['docurl'] .'\'>есть</td></tr>';

    $output .= '</table>';

    return $output;

    bebop_debug($name, $module);
  }

  private static function onGetModulesGroups()
  {
    $map = mcms::getModuleMap();

    $groups = array();

    foreach ($map['modules'] as $modname => $module)
      $groups[$module['group']][$modname] = $module;

    return $groups;
  }

  private static function getAllDashboardIcons()
  {
    if (false and is_array($result = mcms::cache($key = 'dashboardicons')))
      return $result;

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

    ksort($result);

    $trans = array(
      'access' => t('Доступ'),
      'content' => t('Наполнение'),
      'developement' => t('Разработка'),
      'statistics' => t('Статистика'),
      'structure' => t('Структура'),
      );

    $output = '<ul>';

    $cgroup = empty($_GET['cgroup']) ? 'content' : $_GET['cgroup'];

    foreach ($result as $group => $icons) {
      $url = bebop_split_url($icons[0]['href']);
      $url['args']['cgroup'] = $group;

      if ($group == $cgroup)
        $output .= '<li class=\'current\'>';
      else
        $output .= '<li>';

      $output .= mcms::html('a', array(
        'href' => bebop_combine_url($url, false),
        ), array_key_exists($group, $trans) ? $trans[$group] : $group);

      $output .= '<ul>';

      foreach ($icons as $icon) {
        $tmp = mcms::html('a', array(
          'href' => $icon['href'],
          'title' => $icon['description'],
          ), $icon['title']);
        $output .= mcms::html('li', array(), $tmp);
      }

      $output .= '</ul></li>';
    }

    $output .= '</ul>';

    mcms::cache($key, $output);

    return $output;
  }

  public static function getDashboardIcons()
  {
    $icons = array();
    $user = mcms::user();

    if ($user->hasGroup('Structure Managers'))
      $icons[] = array(
        'group' => 'structure',
        'img' => 'img/taxonomy.png',
        'href' => '/admin/?mode=tree&preset=taxonomy',
        'title' => t('Карта сайта'),
        'description' => t('Управление разделами сайта.'),
        );

    if ($user->hasGroup('Schema Managers'))
      $icons[] = array(
        'group' => 'structure',
        'img' => 'img/doctype.png',
        'href' => '/admin/?mode=list&preset=schema',
        'title' => t('Типы документов'),
        );

    if ($user->hasGroup('Content Managers')) {
      $icons[] = array(
        'group' => 'content',
        'img' => 'img/content.png',
        'href' => '/admin/?mode=list&columns=name,class,uid,created',
        'title' => t('Документы'),
        'description' => t('Поиск, редактирование, добавление документов.'),
        );
      if (Node::count(array('published' => 0, '-class' => TypeNode::getInternal())))
        $icons[] = array(
          'group' => 'content',
          'img' => 'img/content.png',
          'href' => '/admin/?mode=list&preset=drafts',
          'title' => t('В модерации'),
          'description' => t('Поиск, редактирование, добавление документов.'),
          );
    }

    if ($user->hasGroup('Developers')) {
      $icons[] = array(
        'group' => 'developement',
        'img' => 'img/constructor.png',
        'href' => '/admin/?mode=tree&preset=pages',
        'title' => t('Конструктор'),
        'description' => t('Управление доменами, страницами и виджетами.'),
        );
      $icons[] = array(
        'group' => 'developement',
        'img' => 'img/cms-widget.png',
        'href' => '/admin/?mode=list&preset=widgets',
        'title' => t('Виджеты'),
        );
      $icons[] = array(
        'group' => 'developement',
        'img' => 'img/constructor.png',
        'href' => '/admin/?mode=modules',
        'title' => t('Модули'),
        );
    }

    if ($user->hasGroup('User Managers')) {
      $icons[] = array(
        'group' => 'access',
        'img' => 'img/user.png',
        'href' => '/admin/?mode=list&preset=users',
        'title' => t('Пользователи'),
        'description' => t('Управление профилями пользователей.'),
        );
      $icons[] = array(
        'group' => 'access',
        'img' => 'img/cms-groups.png',
        'href' => '/admin/?mode=list&preset=groups',
        'title' => t('Группы'),
        'description' => t('Управление группами пользователей.'),
        );
    }

    if ($user->hasGroup('Content Managers'))
      $icons[] = array(
        'group' => 'content',
        'img' => 'img/files.png',
        'href' => '/admin/?mode=list&preset=files',
        'title' => t('Файлы'),
        'description' => t('Просмотр, редактирование и добавление файлов.'),
        );

    if ($user->hasGroup('Content Managers') and Node::count(array('deleted' => 1, '-class' => TypeNode::getInternal())))
      $icons[] = array(
        'group' => 'content',
        'img' => 'img/recycle.png',
        'href' => '/admin/?mode=list&preset=trash',
        'title' => t('Корзина'),
        'description' => t('Просмотр и восстановление удалённых файлов.'),
        'weight' => 10,
        );

    return $icons;
  }
};
