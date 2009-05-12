<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminRPC extends RPCHandler
{
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
      'sitefolder' => os::webpath(MCMS_SITE_FOLDER),
      'query' => $ctx->query(),
      'version' => defined('MCMS_VERSION')
        ? MCMS_VERSION
        : 'unknown',
      'cache' => cache::getInstance()->getName(),
      'memory' => ini_get('memory_limit'),
      'time' => microtime(true) - MCMS_START_TIME,
      'back' => urlencode(MCMS_REQUEST_URI),
      );

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

    unset(cache::getInstance()->route);

    return $ctx->getRedirect();
  }

  public static function on_reload(Context $ctx)
  {
    $next = isset($_SERVER['HTTP_REFERER'])
      ? $_SERVER['HTTP_REFERER']
      : 'admin';

    self::rpc_get_reload($ctx);

    return new Redirect($next);
  }

  /**
   * Вывод списка объектов.
   */
  public static function on_get_list(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML();
  }

  public static function on_get_list_by_type(Context $ctx, $path, array $pathinfo, $type)
  {
    $tmp = new AdminListHandler($ctx, $type);
    return $tmp->getHTML();
  }

  public static function on_get_sections(Context $ctx)
  {
    $tmp = new AdminTreeHandler($ctx);
    $page = new AdminPage($tmp->getHTML('taxonomy'));
    return $page->getResponse($ctx);
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
    $page = new AdminPage($tmp->getHTML($ctx->get('preset')));
    return $page->getResponse($ctx);
  }

  public static function on_get_edit_form(Context $ctx, $path, array $pathinfo, $nid)
  {
    $node = Node::load($nid)->getObject();

    $form = $node->formGet(false);
    $form->addClass('tabbed');

    $page = new AdminPage(html::em('content', array(
      'name' => 'edit',
      ), $form->getXML($node)));
    return $page->getResponse($ctx);
  }

  public static function on_get_create_list(Context $ctx)
  {
    $types = Node::find($ctx->db, array(
      'class' => 'type',
      'name' => $ctx->user->getAccess('c'),
      '-name' => $ctx->user->getAnonymous()->getAccess('c'),
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

  public static function on_get_create_form(Context $ctx, $path, array $pathinfo, $type, $parent_id = null)
  {
    $node = Node::create($type, array(
      'parent_id' => $parent_id,
      'isdictionary' => $ctx->get('dictionary'),
      ));

    if ($nodeargs = $ctx->get('node'))
      foreach ($nodeargs as $k => $v)
        $node->$k = $v;

    $form = $node->formGet(false);
    $form->addClass('tabbed');
    $form->addClass("node-{$type}-create-form");
    $form->action = "?q=nodeapi.rpc&action=create&type={$type}&destination=". urlencode($ctx->get('destination'));

    if ($node->parent_id)
      $form->addControl(new HiddenControl(array(
        'value' => 'parent_id',
        'default' => $node->parent_id,
        )));

    if ($ctx->get('dictionary')) {
      if (null !== ($tmp = $form->findControl('tab_general')))
        $tmp->intro = t('Вы создаёте первый справочник.  Вы сможете использовать его значения в качестве выпадающих списков (для этого надо будет добавить соответствующее поле в нужный <a href=\'@types\'>тип документа</a>).', array('@types' => '?q=admin&cgroup=structure&mode=list&preset=schema'));

      $form->hideControl('tab_sections');

      if (null !== ($ctl = $form->findControl('title')))
        $ctl->label = t('Название справочника');
      if (null !== ($ctl = $form->findControl('name')))
        $ctl->label = t('Внутреннее имя справочника');

      $form->addControl(new HiddenControl(array(
        'value' => 'isdictionary',
        'default' => 1,
        )));
    }

    $page = new AdminPage(html::em('content', array(
      'name' => 'create',
      ), $form->getXML($node)));
    return $page->getResponse($ctx);
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
      '#limit' => 10,
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
        '#limit' => 10,
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
      '#limit' => 10,
      ), array(
      'name' => 'recent',
      'title' => t('Ваши последние документы'),
      'more' => '?q=admin&cgroup=content&action=list&search=uid%3A' . $ctx->user->id,
      ));

    $anon = $ctx->user->getAnonymous();
    $output .= self::getDashboardXML($ctx->db, array(
      'published' => 1,
      'class' => 'type',
      'name' => $ctx->user->getAccess('c'),
      '-name' => $anon->getAccess('c'),
      '#limit' => 10,
      ), array(
      'name' => 'create',
      'title' => t('Добавить документ'),
      'more' => '?q=admin&cgroup=content&action=list&search=published%3A0+-uid%3A' . $ctx->user->id,
      ));

    $output .= self::getDesktopNotes($ctx);

    $output = html::em('content', array(
      'name' => 'dashboard',
      'title' => t('Рабочий стол'),
      ), $output);

    return $output;
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
    return html::wrap('content', Node::findXML($db, $query), $options);
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
