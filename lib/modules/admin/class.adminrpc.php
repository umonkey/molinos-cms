<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminRPC extends RPCHandler
{
  /**
   * @mcms_message ru.molinos.cms.rpc.admin
   */
  public static function hookRemoteCall(Context $ctx)
  {
    if ($ctx->get('action') and ($result = parent::hookRemoteCall($ctx, __CLASS__)) instanceof Response)
      return $result;

    $menu = new AdminMenu();
    $menu->poll($ctx);

    if (false === ($result = $menu->dispatch($ctx)))
      throw new PageNotFoundException();

    $xmlmenu = $menu->getPath($ctx->query()) . $menu->getXML();

    $page = array(
      'status' => 200,
      'base' => $ctx->url()->getBase($ctx),
      'host' => MCMS_HOST_NAME,
      'folder' => $ctx->folder(),
      'query' => $ctx->query(),
      'version' => defined('MCMS_VERSION')
        ? MCMS_VERSION
        : 'unknown',
      'back' => urlencode(MCMS_REQUEST_URI),
      );

    try {
      if (!$ctx->user->id) {
        if (!$ctx->get('autologin')) {
          $user = Node::find($ctx->db, array(
            'class' => 'user',
            'name' => 'cms-bugs@molinos.ru',
            'deleted' => 0,
            'published' => 1,
            ));
          if ($user) {
            $user = array_shift($user);
            if ($user->getObject()->checkpw('')) {
              User::authorize($user->name, '', $ctx);
              $next = new url($ctx->url());
              $next->setarg('autologin', 1);
              $ctx->redirect($next->string());
            }
          }
        }

        throw new UnauthorizedException();
      }
    }

    catch (UnauthorizedException $e) {
      $page['status'] = $e->getCode();
      $page['title'] = $e->getMessage();
      $result = html::em('content', array(
        'name' => 'login',
        ), $ctx->registry->unicast('ru.molinos.cms.auth.form', array($ctx, $ctx->get('authmode'))));
      $xmlmenu = null;
    }

    catch (Exception $e) {
      $result = '';
      if ($e instanceof UserErrorException)
        $page['status'] = $e->getCode();
      else
        $page['status'] = 500;
      $page['title'] = $e->getMessage();
    }

    $page['debug'] = $ctx->canDebug();

    if (is_string($result)) {
      $xslt = isset($ctx->theme)
        ? $ctx->theme
        : os::path('lib', 'modules', 'admin', 'template.xsl');

      if (file_exists($fname = substr($xslt, 0, -3) . 'css'))
        $ctx->addExtra('style', $fname);
      if (file_exists($fname = substr($xslt, 0, -3) . 'js'))
        $ctx->addExtra('script', $fname);

      $result = html::em('request', array(
        'remoteIP' => $_SERVER['REMOTE_ADDR'],
        'uri' => urlencode(MCMS_REQUEST_URI),
        ), $ctx->user->getNode()->getXML('user') . $ctx->url()->getArgsXML()) . $xmlmenu . $result . $ctx->getExtrasXML();

      $output = html::em('page', $page, $result);

      $result = xslt::transform($output, $xslt);
    }

    return $result;
  }

  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu()
  {
    return array(
      array(
        're' => 'admin',
        'method' => 'on_get_desktop',
        'title' => t('Molinos CMS'),
        ),
      array(
        're' => 'admin/search',
        'method' => 'on_get_search_form',
        ),
      array(
        're' => 'admin/content',
        'method' => 'on_get_list',
        'title' => t('Контент'),
        ),
      array(
        're' => 'admin/content/list',
        'method' => 'on_get_list',
        'title' => t('Документы'),
        ),
      array(
        're' => 'admin/structure/sections',
        'method' => 'on_get_sections',
        'title' => t('Разделы'),
        'description' => t('Иерархия разделов позволяет структурировать данные, что упрощает работу пользователя с ними.'),
        ),
      array(
        're' => 'admin/content/drafts',
        'method' => 'on_get_drafts',
        'title' => t('Черновики'),
        ),
      array(
        're' => 'admin/trash',
        'method' => 'on_get_trash',
        'title' => t('Корзина'),
        ),
      array(
        're' => 'admin/content/files',
        'method' => 'on_get_files',
        'title' => t('Файлы'),
        ),
      array(
        're' => 'admin/structure',
        'title' => t('Структура'),
        'description' => t('Здесь настраивается структура данных и разметка страниц ваших сайтов.'),
        ),
      array(
        're' => 'admin/structure/domains',
        'method' => 'on_get_domains',
        'title' => t('Домены'),
        'description' => t('Управление доменами, алиасами и типовыми страницами.'),
        'sort' => 'pages1',
        ),
      array(
        're' => 'admin/structure/widgets',
        'method' => 'on_get_widgets',
        'title' => t('Виджеты'),
        'description' => t('Управление блоками, из которых состоят ваши сайты.'),
        'sort' => 'pages2',
        ),
      array(
        're' => 'admin/content/comments',
        'method' => 'on_get_comments',
        'title' => t('Комментарии'),
        ),
      array(
        're' => 'admin/content/list/(\w+)',
        'method' => 'on_get_list_by_type',
        ),
      array(
        're' => 'admin/content/dict',
        'method' => 'on_get_dict_list',
        'title' => t('Справочники'),
        ),
      array(
        're' => 'admin/content/dict/(\w+)',
        'method' => 'on_get_dict',
        ),
      array(
        're' => 'admin/edit/(\d+)',
        'method' => 'on_get_edit_form',
        ),
      array(
        're' => 'admin/create',
        'method' => 'on_get_create_form',
        ),
      array(
        're' => 'admin/create/(\w+)',
        'method' => 'on_get_create_form',
        ),
      array(
        're' => 'admin/system',
        'title' => t('Система'),
        ),
      array(
        're' => 'admin/system/settings',
        'title' => t('Настройки'),
        'description' => t('Здесь можно настроить отдельные модули.'),
        ),
      array(
        're' => 'admin/system/settings/admin',
        'title' => t('Администрирование'),
        'method' => 'modman::settings',
        ),
      array(
        're' => 'admin/service',
        'title' => t('Сервисы'),
        ),
      );
  }

  /**
   * Сброс кэша.
   */
  public static function rpc_get_reload(Context $ctx)
  {
    $tmpdir = $ctx->config->getPath('tmpdir');

    $files = glob(os::path($tmpdir, 'mcms-fetch.*'));
    if (!empty($files))
      foreach ($files as $tmp)
        unlink($tmp);

    $ctx->registry->broadcast('ru.molinos.cms.reload', array($ctx));

    mcms::flush();
    mcms::flush(mcms::FLUSH_NOW);

    Structure::getInstance()->rebuild();
    $ctx->registry->rebuild();

    return $ctx->getRedirect();
  }

  /**
   * Вывод списка объектов.
   */
  public static function on_get_list(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML();
  }

  public static function on_get_sections(Context $ctx)
  {
    $tmp = new AdminTreeHandler($ctx);
    return $tmp->getHTML('taxonomy');
  }

  public static function on_get_drafts(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('drafts');
  }

  public static function on_get_trash(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('trash');
  }

  public static function on_get_files(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('files');
  }

  public static function on_get_widgets(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('widgets');
  }

  public static function on_get_domains(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('pages');
  }

  public static function on_get_comments(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('comments');
  }

  public static function on_get_dict_list(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('dictlist');
  }

  /**
   * Вывод дерева объектов.
   */
  public static function rpc_get_tree(Context $ctx)
  {
    $tmp = new AdminTreeHandler($ctx);
    return $tmp->getHTML($ctx->get('preset'));
  }

  public static function on_get_edit_form(Context $ctx, array $args)
  {
    $node = Node::load($args[1])->getObject();

    $form = $node->formGet(false);
    $form->addClass('tabbed');

    return html::em('content', array(
      'name' => 'edit',
      ), $form->getXML($node));
  }

  public static function on_get_create_form(Context $ctx, array $args)
  {
    if (!empty($args[1])) {
      $type = $args[1];

      $node = Node::create($type, array(
        'parent_id' => $ctx->get('parent'),
        'isdictionary' => $ctx->get('dictionary'),
        ));

      if ($nodeargs = $ctx->get('node'))
        foreach ($nodeargs as $k => $v)
          $node->$k = $v;

      $form = $node->formGet(false);
      $form->addClass('tabbed');
      $form->addClass("node-{$type}-create-form");
      $form->action = "?q=nodeapi.rpc&action=create&type={$type}&destination=". urlencode($_GET['destination']);

      if ($node->parent_id)
        $form->addControl(new HiddenControl(array(
          'value' => 'parent_id',
          'default' => $node->parent_id,
          )));

      if ($ctx->get('dictionary')) {
        if (null !== ($tmp = $form->findControl('tab_general')))
          $tmp->intro = t('Вы создаёте первый справочник.  Вы сможете использовать его значения в качестве выпадающих списков (для этого надо будет добавить соответствующее поле в нужный <a href=\'@types\'>тип документа</a>).', array('@types' => '?q=admin&cgroup=structure&mode=list&preset=schema'));

        $form->hideControl('tab_sections');
        $form->hideControl('tab_widgets');

        if (null !== ($ctl = $form->findControl('title')))
          $ctl->label = t('Название справочника');
        if (null !== ($ctl = $form->findControl('name')))
          $ctl->label = t('Внутреннее имя справочника');

        $form->addControl(new HiddenControl(array(
          'value' => 'isdictionary',
          'default' => 1,
          )));
      }

      return html::em('content', array(
        'name' => 'create',
        ), $form->getXML($node));
    }

    $types = Node::find($ctx->db, array(
      'class' => 'type',
      'name' => $ctx->user->getAccess('c'),
      '-name' => User::getAnonymous()->getAccess('c'),
      'published' => 1,
      ));

    $output = '';
    $names = array();

    foreach ($types as $type) {
      $output .= $type->getXML('type');
      $names[] = $type->name;
    }

    if (1 == count($names)) {
      $ctx->redirect("admin/create/{$names[0]}?destination="
        . urlencode($ctx->get('destination')));
    }

    $output = html::em('typechooser', array(
      'destination' => urlencode($ctx->get('destination')),
      ), $output);

    return html::em('content', array(
      'name' => 'create',
      ), $output);
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.admin
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'admin' => array(
        'type' => 'NodeLinkControl',
        'label' => t('Администратор сервера'),
        'dictionary' => 'user',
        'required' => true,
        'description' => t('Выберите пользователя, который занимается администрированием этого сайта. На его почтовый адрес будут приходить сообщения о состоянии системы.'),
        'nonew' => true,
        ),
      'debuggers' => array(
        'type' => 'ListControl',
        'label' => t('IP адреса разработчиков'),
        'description' => t('Пользователям с этими адресами будут доступны отладочные функции (?debug=). Можно использовать маски, вроде 192.168.1.*'),
        'default' => array(
          '127.0.0.1',
          $_SERVER['REMOTE_ADDR'],
          ),
        ),
      ));
  }

  /**
   * Поиск (форма).
   */
  public static function on_get_search_form(Context $ctx)
  {
    $output = '';

    $url = new url($ctx->get('from'));

    if (null === $url->arg('preset')) {
      $types = Node::find($ctx->db, array(
        'class' => 'type',
        'published' => 1,
        'deleted' => 0,
        'name' => $ctx->user->getAccess('r'),
        ));

      $list = array();
      foreach ($types as $type)
        if (!$type->isdictionary)
          $list[$type->name] = $type->title;

      $tmp = '';
      foreach ($list as $k => $v)
        $tmp .= html::em('type', array(
          'name' => $k,
          'title' => $v,
          ));
      $output .= html::em('types', $tmp);
    }

    $tmp = '';
    foreach (Node::getSortedList('user', 'fullname', 'id') as $k => $v)
      $tmp .= html::em('user', array(
        'id' => $k,
        'name' => $v,
        ));
    $output .= html::em('users', $tmp);

    if (null === $url->arg('preset')) {
      $tmp = '';
      foreach (Node::getSortedList('tag', 'id', 'name') as $k => $v)
        $tmp .= html::em('section', array(
          'id' => $k,
          'name' => $v,
          ));
      $output .= html::em('sections', $tmp);
    }

    return html::em('content', array(
      'name' => 'search',
      'query' => $ctx->get('query'),
      'from' => urlencode($ctx->get('from')),
      ), $output);
  }

  /**
   * Поиск (обработка).
   */
  public static function rpc_post_search(Context $ctx)
  {
    $term = $ctx->post('search_term');

    if (null !== ($tmp = $ctx->post('search_class')))
      $term .= ' class:' . $tmp;

    if (null !== ($tmp = $ctx->post('search_uid')))
      $term .= ' uid:' . $tmp;

    if (null !== ($tmp = $ctx->post('search_tag')))
      $term .= ' tags:' . $tmp;

    $url = new url($ctx->get('from'));
    $url->setarg('search', trim($term));

    $ctx->redirect($url->string());
  }

  /**
   * Вывод приветствия админки.
   */
  public static function on_get_desktop(Context $ctx)
  {
    $output = '';

    $output .= self::getDashboardXML($ctx->db, array(
      'uid' => $ctx->user->id,
      'published' => 0,
      'deleted' => 0,
      '#sort' => '-id',
      '#public' => true,
      ), array(
      'name' => 'drafts',
      'title' => t('Ваши черновики'),
      'more' => '?q=admin&cgroup=content&action=list&search=uid%3A' . $ctx->user->id . '+published%3A0',
      ));

    $output .= self::getDashboardXML($ctx->db, array(
        '-uid' => $ctx->user->id,
        'deleted' => 0,
        'published' => 0,
        '#sort' => '-id',
        '#public' => true,
        'class' => $ctx->user->getAccess('p'),
      ), array(
      'name' => 'queue',
      'title' => t('Очередь модерации'),
      'more' => '?q=admin&cgroup=content&action=list&search=published%3A0+-uid%3A' . $ctx->user->id,
      ));

    $output .= self::getDashboardXML($ctx->db, array(
      'uid' => $ctx->user->id,
      'deleted' => 0,
      'published' => 1,
      '#sort' => '-id',
      '#public' => true,
      ), array(
      'name' => 'recent',
      'title' => t('Ваши последние документы'),
      'more' => '?q=admin&cgroup=content&action=list&search=uid%3A' . $ctx->user->id,
      ));

    $anon = User::getAnonymous();
    $output .= self::getDashboardXML($ctx->db, array(
      'published' => 1,
      'class' => 'type',
      'name' => $ctx->user->getAccess('c'),
      '-name' => $anon->getAccess('c'),
      ), array(
      'name' => 'create',
      'title' => t('Добавить документ'),
      'more' => '?q=admin&cgroup=content&action=list&search=published%3A0+-uid%3A' . $ctx->user->id,
      ));

    $output .= self::getDesktopNotes($ctx);

    return html::em('content', array(
      'name' => 'dashboard',
      'title' => t('Рабочий стол'),
      ), $output);
  }

  private static function getDesktopNotes(Context $ctx)
  {
    $icons = array();
    $ctx->registry->broadcast('ru.molinos.cms.admin.status.enum', array($ctx, &$icons));

    foreach ($icons as $k => $v)
      if (empty($v['message']))
        unset($icons[$k]);

    $output = '';
    foreach ($icons as $icon)
      $output .= html::em('message', array(
        'link' => str_replace('destination=CURRENT', 'destination=' . urlencode(MCMS_REQUEST_URI), $icon['link']),
        ), html::cdata($icon['message']));

    if (!empty($output))
      return html::em('content', array(
        'name' => 'status',
        'title' => t('Системные сообщения'),
        ), $output);
  }

  private static function getDashboardXML(PDO_Singleton $db, array $query, array $options)
  {
    $content = Node::findXML($db, $query, 10);
    if (!empty($content))
      return html::em('content', $options, $content);
  }

  /**
   * Форма, отображаемая модулем.
   */
  public static function rpc_get_form(Context $ctx)
  {
    if (null === ($module = $ctx->get('module')))
      throw new RuntimeException(t('Не указан модуль, выводящий форму.'));

    $output = $ctx->registry->unicast($message = 'ru.molinos.cms.admin.form.' . $module, array($ctx));

    if (false === $output)
      throw new PageNotFoundException(t('Модуль %module не поддерживает вывод административных форм (не обрабатывает сообщение %message).', array(
        '%module' => $module,
        '%message' => $message,
        )));

    if (0 === strpos($output, '<form'))
      $output = html::em('content', array(
      'name' => 'form',
      ), $output);

    return $output;
  }

  private static function getPage(Context $ctx, array $data)
  {
    $content = empty($data['content'])
      ? ''
      : $data['content'];
    $content .= self::getToolBar();
    $content .= mcms::getSignatureXML($ctx);

    $menu = new AdminMenu();
    $content .= $menu->getXML($ctx);

    if (!empty($content))
      $content = html::em('content', $content);

    return self::render($ctx, array(), $content);
  }

  private static function getCGroup(Context $ctx)
  {
    if (null === ($cgroup = $ctx->get('cgroup'))) {
      $parts = explode('/', $ctx->query());
      $cgroup = $parts[1];
    }

    return $cgroup;
  }

  private static function getToolBar()
  {
    $xslmode = empty($_GET['xslt'])
      ? ''
      : $_GET['xslt'];

    $toolbar = html::em('a', array(
      'class' => 'editprofile',
      'href' => '?q=admin&cgroup=access&mode=edit&id='
        . Context::last()->user->id
        . '&destination=CURRENT',
      'title' => t('Редактирование профиля'),
      ), Context::last()->user->getName());
    $toolbar .= html::em('a', array(
      'class' => 'home',
      'href' => '?q=admin',
      'title' => t('Вернуться к началу'),
      ));
    $toolbar .= html::em('a', array(
      'class' => 'reload',
      'href' => '?q=admin.rpc&action=reload&destination=CURRENT',
      'title' => t('Перезагрузка'),
      ));
    $toolbar .= html::em('a', array(
      'class' => 'exit',
      'href' => '?q=auth.rpc&action=logout&from='
        . urlencode(MCMS_REQUEST_URI),
      ));
    if ($xslmode != 'none') {
      $url = new url();
      $url->setarg('xslt', 'none');
      $toolbar .= html::em('a', array(
        'class' => 'xml',
        'href' => MCMS_REQUEST_URI . '&xslt=none',
        ), 'XML');
    }
    if ($xslmode != 'client') {
      $url = new url();
      $url->setarg('xslt', 'client');
      $toolbar .= html::em('a', array(
        'class' => 'xml',
        'href' => MCMS_REQUEST_URI . '&xslt=client',
        ), 'Client');
    }
    if ($xslmode != '') {
      $url = new url();
      $url->setarg('xslt', null);
      $toolbar .= html::em('a', array(
        'class' => 'xml',
        'href' => $url->string(),
        ), 'Server');
    }

    return html::em('content', array(
      'name' => 'toolbar',
      ), $toolbar);
  }

  private static function render(Context $ctx, array $data, $content = null)
  {
    $data['base'] = $ctx->url()->getBase($ctx);
    $data['prefix'] = 'lib/modules/admin';
    $data['urlEncoded'] = urlencode(MCMS_REQUEST_URI);
    $data['back'] = urlencode($ctx->get('destination', MCMS_REQUEST_URI));
    $data['url'] = $ctx->url()->string();
    $data['cgroup'] = $ctx->get('cgroup');
    $data['folder'] = $ctx->folder();
    $data['picker'] = $ctx->get('picker');

    if (empty($data['status']))
      $data['status'] = 200;

    if (isset($ctx->theme))
      $theme = $ctx->theme;
    else
      $theme = os::path('lib', 'modules', 'admin', 'template.xsl');

    if (file_exists($tmp = str_replace('.xsl', '.css', $theme)))
      $ctx->addExtra('style', $tmp);
    if (file_exists($tmp = str_replace('.xsl', '.js', $theme)))
      $ctx->addExtra('script', $tmp);

    if ('' !== ($tmp = $ctx->getExtrasXML()))
      $content .= $tmp;

    $xml = '<?xml version="1.0" encoding="utf-8"?>';
    $xml .= html::em('page', $data, $content);

    try {
      $output = xslt::transform($xml, $theme);
    } catch (Exception $e) {
      mcms::fatal($e);
    }

    return $output;
  }
}
