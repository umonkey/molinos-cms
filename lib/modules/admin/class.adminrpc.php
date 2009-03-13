<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminRPC extends RPCHandler
{
  /**
   * @mcms_message ru.molinos.cms.rpc.admin
   */
  public static function hookRemoteCall(Context $ctx)
  {
    $page = array(
      'status' => 200,
      'base' => $ctx->url()->getBase($ctx),
      'host' => MCMS_HOST_NAME,
      'folder' => $ctx->folder(),
      'version' => defined('MCMS_VERSION')
        ? MCMS_VERSION
        : 'unknown',
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

      if (is_string($result = parent::hookRemoteCall($ctx, __CLASS__))) {
        $menu = new AdminMenu();
        $result .= $menu->getXML($ctx);
      }
    } catch (Exception $e) {
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
        'uri' => urlencode($_SERVER['REQUEST_URI']),
        ), $ctx->user->getNode()->getXML('user') . $ctx->url()->getArgsXML()) . html::em('blocks', $result) . $ctx->getExtrasXML();

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

    mcms::flush();
    mcms::flush(mcms::FLUSH_NOW);

    Structure::getInstance()->rebuild();
    $ctx->registry->rebuild();

    return $ctx->getRedirect();
  }

  /**
   * Вывод списка объектов.
   */
  public static function rpc_get_list(Context $ctx)
  {
    $module = $ctx->get('module', 'admin');

    if (false === ($result = $ctx->registry->unicast('ru.molinos.cms.admin.list', array($ctx)))) {
      $tmp = new AdminListHandler($ctx);
      $result = $tmp->getHTML($ctx->get('preset'));
    }

    return $result;
  }

  /**
   * Вывод дерева объектов.
   */
  public static function rpc_get_tree(Context $ctx)
  {
    $tmp = new AdminTreeHandler($ctx);
    return $tmp->getHTML($ctx->get('preset'));
  }

  /**
   * Редактирование объектов.
   */
  public static function rpc_get_edit(Context $ctx)
  {
    if (null === ($nid = $ctx->get('node')))
      throw new PageNotFoundException();

    $node = Node::load($nid)->getObject();

    $form = $node->formGet(false);
    $form->addClass('tabbed');

    return html::em('block', array(
      'name' => 'edit',
      ), $form->getXML($node));
  }

  /**
   * Добавление объекта.
   */
  public static function rpc_get_create(Context $ctx)
  {
    if (null !== $ctx->get('type')) {
      $node = Node::create($type = $ctx->get('type'), array(
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

      return html::em('block', array(
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

    if (1 == count($names))
      $ctx->redirect("?q=admin.rpc&cgroup=content&action=create&type={$names[0]}&destination="
        . urlencode($ctx->get('destination')));

    $output = html::em('typechooser', array(
      'destination' => urlencode($ctx->get('destination')),
      ), $output);

    return html::em('block', array(
      'name' => 'create',
      ), $output);
  }

  /**
   * Поиск (форма).
   */
  public static function rpc_get_search(Context $ctx)
  {
    $output = '';

    $url = new url($ctx->get('destination'));

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

    return html::em('block', array(
      'name' => 'search',
      'query' => $ctx->get('query'),
      'from' => urlencode($ctx->get('destination')),
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
    $url->setarg('search', $term);

    $ctx->redirect($url->string());
  }

  /**
   * Вывод приветствия админки.
   */
  public static function rpc_get_default(Context $ctx)
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

    return html::em('block', array(
      'name' => 'dashboard',
      'title' => t('Рабочий стол'),
      ), $output);
  }

  private static function getDashboardXML(PDO_Singleton $db, array $query, array $options)
  {
    $content = Node::findXML($db, $query, 10);
    if (!empty($content))
      return html::em('block', $options, $content);
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
      $output = html::em('block', array(
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
      $content = html::em('blocks', $content);

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
      'href' => '?q=user.rpc&action=logout&from='
        . urlencode($_SERVER['REQUEST_URI']),
      ));
    if ($xslmode != 'none') {
      $url = new url();
      $url->setarg('xslt', 'none');
      $toolbar .= html::em('a', array(
        'class' => 'xml',
        'href' => $_SERVER['REQUEST_URI'] . '&xslt=none',
        ), 'XML');
    }
    if ($xslmode != 'client') {
      $url = new url();
      $url->setarg('xslt', 'client');
      $toolbar .= html::em('a', array(
        'class' => 'xml',
        'href' => $_SERVER['REQUEST_URI'] . '&xslt=client',
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

    return html::em('block', array(
      'name' => 'toolbar',
      ), $toolbar);
  }

  private static function render(Context $ctx, array $data, $content = null)
  {
    $data['base'] = $ctx->url()->getBase($ctx);
    $data['prefix'] = 'lib/modules/admin';
    $data['urlEncoded'] = urlencode($_SERVER['REQUEST_URI']);
    $data['back'] = urlencode($ctx->get('destination', $_SERVER['REQUEST_URI']));
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
