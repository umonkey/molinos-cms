<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    $action = $ctx->get('action');

    $next = $ctx->get('destination', '');

    switch ($ctx->get('action')) {
    case 'reload':
      $tmpdir = mcms::config('tmpdir');

      foreach (glob($tmpdir .'/mcms-fetch.*') as $tmp)
        unlink($tmp);

      mcms::flush();
      mcms::flush(mcms::FLUSH_NOW);

      Structure::getInstance()->rebuild();

      break;

    case '404':
      mcms::user()->checkAccess('u', 'type');

      if ('update' == $ctx->get('mode')) {
        if (null !== ($src = $ctx->post('src')) and null !== ($dst = $ctx->post('dst'))) {
          $ctx->db->exec("UPDATE `node__fallback` SET `new` = ? "
            ."WHERE `old` = ?", array($dst, $src));
        }
      } elseif ('delete' == $ctx->get('mode')) {
        $ctx->db->exec("DELETE FROM `node__fallback` WHERE `old` = ?",
          array($ctx->get('src')));
      }

      $next = $ctx->get('destination');
      break;

    case 'reindex':
      if (NodeIndexer::run())
        $next = '?q=admin.rpc&action=reindex';
      else
        $next = 'admin';
      break;

    case 'modlist':
      self::hookModList($ctx);
      break;

    case 'modconf':
      self::hookModConf($ctx);
      $ctx->redirect('?q=admin&cgroup=structure&mode=modules');

    case 'search':
      $terms = array();

      foreach (array('term' => '', 'author' => 'uid:', 'type' => 'class:') as $k => $v)
        if (null !== ($tmp = $ctx->post('search_'. $k)) and !empty($tmp))
          $terms[] = $v . $tmp;

      if ($tmp = $ctx->post('search_tags')) {
        if ($ctx->post('search_tags_recurse')) {
          if (is_array($ids = $ctx->db->getResultsV('id', 'SELECT `n`.`id` FROM `node` `n`, `node` `parent` WHERE `n`.`class` = \'tag\' AND `n`.`deleted` = 0 AND `parent`.`id` = :tid AND `n`.`left` >= `parent`.`left` AND `n`.`right` <= `parent`.`right`', array(':tid' => $tmp))))
            $tmp = join(',', $ids);
        }
        $terms[] = 'tags:'. $tmp;
      }

      $url = new url($ctx->post('search_from'));
      $url->setarg('search', trim(join(' ', $terms)));
      $url->setarg('page', null);

      $next = $url->string();

      break;

    default:
      if ('GET' == $ctx->method())
        return self::onGet(self::fixCleanURLs($ctx));
      else
        return mcms::dispatch_rpc(__CLASS__, $ctx);
    }

    $ctx->redirect($next);
  }

  private static function hookModList(Context $ctx)
  {
    if ('POST' != $_SERVER['REQUEST_METHOD'])
      throw new PageNotFoundException();

    mcms::user()->checkAccess('u', 'moduleinfo');

    mcms::enableModules($ctx->post('selected', array()));

    Structure::getInstance()->rebuild();
  }

  private static function hookModConf(Context $ctx)
  {
    $conf = array();

    mcms::user()->checkAccess('u', 'moduleinfo');

    foreach ($ctx->post as $k => $v) {
      if (substr($k, 0, 7) == 'config_' and !empty($v))
        $conf[substr($k, 7)] = $v;
    }

    if ('admin' == $ctx->get('module')) {
      $debuggers = empty($conf['debuggers'])
        ? null
        : preg_split('/,\s*/', $conf['debuggers'], -1, PREG_SPLIT_NO_EMPTY);

      $cfg = Config::getInstance();
      $cfg->debuggers = $debuggers;
      $cfg->write();

      if (array_key_exists('debuggers', $conf))
        unset($conf['debuggers']);
    }

    if (count($tmp = array_values(Node::find(array('class' => 'moduleinfo', 'name' => $ctx->get('module'))))))
      $node = $tmp[0];
    else
      $node = Node::create('moduleinfo', array(
        'name' => $ctx->get('module'),
        'published' => true,
        ));

    $node->config = $conf;
    $node->save();
  }

  private static function fixCleanURLs(Context $ctx)
  {
    $id = null;

    if (count($m = explode('/', $ctx->query())) > 1) {
      $url = new url($ctx->url());

      switch (count($m)) {
      case 5:
        $url->setarg('subid', $m[4]);
      case 4:
        $url->setarg('preset', $id = $m[3]);
      case 3:
        $url->setarg('mode', $m[2]);
        if ('edit' == $m[2])
          $url->setarg('id', $id);
      case 2:
        $url->setarg('cgroup', $m[1]);
      }

      $ctx = new Context(array(
        'url' => $url,
        ));
    }

    return $ctx;
  }

  public static function onGet(Context $ctx)
  {
    if (null !== ($tmp = self::checkAccessAndAutoLogin($ctx)))
      return $tmp;

    $result = array();

    $m = $ctx->get('module');
    if (null === ($module = $ctx->get('module')))
      $result['content'] = self::onGetInternal($ctx);

    elseif (!count($classes = Loader::getImplementors('iAdminUI', $module))) {
      throw new PageNotFoundException(t('Запрошенный модуль (%name) '
        .'не поддерживает работу с административным интерфейсом.',
          array('%name' => $module)));
    }

    elseif (!class_exists($classes[0])) {
      mcms::flog(t('Класс %class, используемый админкой, не мог быть загружен.', array('%class' => $classes[0])));
    }

    else {
      $result['content'] = call_user_func_array(array($classes[0], 'onGet'), array($ctx));
    }

    $am = new AdminMenu();
    $result['dashboard'] = $am->__toString();

    return self::getPage($result);
  }

  private static function onGetInternal(Context $ctx)
  {
    if ($ctx->method('post')) {
      $url = new url($ctx->url());
      $url->setarg('search', $ctx->post('search'));
      return new Redirect($url->string());
    }

    switch ($mode = $ctx->get('mode', 'status')) {
    case 'search':
    case 'list':
    case 'tree':
    case 'edit':
    case 'create':
    case 'status':
    case 'modules':
    case 'drafts':
    case 'exchange':
    case 'trash':
    case '404':
      $method = 'onGet'. ucfirst(strtolower($mode));
      return call_user_func_array(array(__CLASS__, $method), array($ctx));
    default:
      throw new PageNotFoundException();
    }
  }

  private static function onGetList(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML($ctx->get('preset'));
  }

  private static function onGetTree(Context $ctx)
  {
    $tmp = new AdminTreeHandler($ctx);
    return $tmp->getHTML($ctx->get('preset'));
  }

  private static function onGetExchange(Context $ctx)
  {
    $result = $ctx->get('result');
    if ($result=='importok') {
       $resulttext = new  InfoControl(array('text'=>'Импорт прошёл успешно'));
       return $resulttext->getHTML(array());
    }

    $form = new Form(array(
      'title' => t('Экспорт/импорт сайта в формате XML'),
      'description' => t("Необходимо выбрать совершаемое вами действие"),
      'action' => '?q=exchange.rpc',
      'class' => '',
      ));

    $resstr = array (
      'noprofilename' => 'Ошибка: не введено имя профиля',
      'noimpprofile' => 'Ошибка: не выбран профиль для импорта',
      'notopenr' => 'Ошибка: невозможно открыть файл на чтение',
      'badfiletype' => 'Неподдерживаемый тип файла. Файл должен быть формата XML или ZIP'
      );

    if ($result)
      $form->addControl(new  InfoControl(array('text'=> $resstr[$result])));

    $form->addControl(new EnumRadioControl(array(
       'value' => 'exchmode',
       'label' => t('Действие'),
       'default' =>   'import',
       'options' => array(
          'export' => t('Экспорт'),
          'import' => t('Импорт'),
          ),
        )));
    $form->addControl(new InfoControl(array('text'=>'Экспорт')));

    $form->addControl(new TextAreaControl(array(
      'value' => 'expprofiledescr',
      'label' => t('Описание профиля'),
      'description' => t("Краткое описание профиля."),
      'rows' => 3
      )));

    $form->addControl(new InfoControl(array('text' => 'Импорт')));
    $plist = ExchangeModule::getProfileList();
    $options = array();

    for ($i = 0; $i < count($plist); $i++) {
      $pr = $plist[$i];
      $options[$pr['filename']] = $pr['name'];
    }

    $form->addControl(new AttachmentControl(array(
      'label' => t('Выберите импортируемый профиль'),
      'value' => 'impprofile'
      )));

    $form->addControl(new SubmitControl(array(
      'text' => t('Произвести выбранную операцию'),
      )));

      return $form->getHTML(array());
  }

  private static function onGetEdit(Context $ctx)
  {
    // Отдельный вывод редактора ошибок 404.
    if ('404' == $ctx->get('preset') and null !== ($src = $ctx->get('subid'))) {
      $dst = $ctx->db->fetch("SELECT `new` FROM `node__fallback` "
        ."WHERE `old` = ?", array($src));

      $form = new Form(array(
        'method' => 'post',
        'action' => '?q=admin.rpc&action=404&mode=update'
          .'&destination='. urlencode($ctx->get('destination')),
        'title' => t('Подмена отсутствующей страницы'),
        ));
      $form->addControl(new TextLineControl(array(
        'value' => 'src', 
        'label' => t('Запрашиваемый адрес'),
        'default' => $src,
        'readonly' => true,
        )));
      $form->addControl(new TextLineControl(array(
        'value' => 'dst',
        'label' => t('Перенаправлять на'),
        'default' => $dst,
        'description' => t('Обычно это — относительная ссылка, '
          .'вроде node/123, но может быть и внешней, например: '
          .'http://www.google.com/'),
        )));
      $form->addControl(new SubmitControl());

      return $form->getHTML(array());
    }

    if (null === ($nid = $ctx->get('id')))
      throw new PageNotFoundException();

    $node = Node::load(array(
      'id' => $nid,
      'deleted' => array(0, 1),
      '#recurse' => true
      ));

    $form = $node->formGet(false);
    $form->addClass('tabbed');

    return $form->getHTML($node);
  }

  private static function onGetCreate(Context $ctx)
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
          $tmp->intro = t('Вы создаёте первый справочник.  Вы сможете использовать его значения в качестве выпадающих списков (для этого надо будет добавить соответствующее поле в нужный <a href=\'@types\'>тип документа</a>).', array('@types' => 'admin/?cgroup=structure&mode=list&preset=schema'));

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

      return $form->getHTML($node);
    }

    $types = Node::find(array(
      'class' => 'type',
      '-name' => TypeNode::getInternal(),
      ));

    $output = '<dl>';

    foreach ($types as $type) {
      if (mcms::user()->hasAccess('c', $type->name)) {
        $output .= '<dt>';
        $output .= html::em('a', array(
          'href' => "?q=admin&mode=create&type={$type->name}&destination=". urlencode($_GET['destination']),
          ), $type->title);
        $output .= '</dt>';

        if (isset($type->description))
          $output .= '<dd>'. $type->description .'</dd>';
      }
    }

    $output .= '</dl>';

    return '<h2>Какой документ вы хотите создать?</h2>'. $output;
  }

  private static function onGetStatus(Context $ctx)
  {
    $m = new AdminMenu();

    return $m->getDesktop();
  }

  private static function getNoPassword()
  {
    if (!empty($_GET['noautologin'])) {
      try {
        $node = Node::load(array(
          'class' => 'user',
          'name' => 'cms-bugs@molinos.ru',
          ));
        if (empty($node->password)) {
          return t('<p>У административного аккаунта нет пароля, '
            .'<a href=\'@url\'>установите</a> его.</p>', array(
              '@url' => '?q=admin&?cgroup=access&mode=edit&id='
                .$node->id .'&destination=CURRENT',
              ));
        } else {
          $url = new url();
          $url->setarg('noautologin', null);

          $r = new Redirect($url->string());
          $r->send();
        }
      } catch (ObjectNotFoundException $e) { }
    }
  }

  private static function onGetModules(Context $ctx)
  {
    switch ($ctx->get('action')) {
    case 'info':
      return self::onGetModuleInfo($ctx->get('name'));

    case 'config':
      $form = mcms::invoke_module($ctx->get('name'), 'iModuleConfig', 'formGetModuleConfig');

      if (!($form instanceof Form))
        throw new PageNotFoundException();

      $data = array();

      if (is_array($tmp = mcms::modconf($ctx->get('name'))))
        foreach ($tmp as $k => $v)
          $data['config_'. $k] = $v;

      if (empty($form->title))
        $form->title = t('Настройка модуля %name', array('%name' => $ctx->get('name')));

      $form->action = bebop_combine_url($tmp = array(
        'args' => array(
          'q' => 'admin.rpc',
          'module' => $ctx->get('name'),
          'action' => 'modconf',
          'destination' => $_SERVER['REQUEST_URI'],
          ),
        ), false);

      $form->addControl(new SubmitControl(array(
        'text' => t('Сохранить'),
        )));

      return $form->getHTML(Control::data($data));
    }

    $tmp = new ModuleAdminUI();
    return $tmp->getList();
  }

  private static function onGetModuleInfo($name)
  {
    $map = mcms::getModuleMap();

    if (empty($map['modules'][$name]))
      throw new PageNotFoundException();

    $module = $map['modules'][$name];

    $classes = $module['classes'];
    sort($classes);

    $output = "<h2>Информация о модуле mod_{$name}</h2>";
    $output .= '<table class=\'modinfo\'>';
    $output .= '<tr><th>Описание:</th><td>'. $module['name']['ru'] .'</td></tr>';
    $output .= '<tr><th>Классы:</th><td>'. join(', ', $classes) .'</td></tr>';

    if (!empty($module['interfaces']))
      $output .= '<tr><th>Интерфейсы:</th><td>'. join(', ', $module['interfaces']) .'</td></tr>';

    if (!empty($module['version']))
      $output .= '<tr><th>Версия CMS:</th><td>≥'. $module['version'] .'</td></tr>';

    if (!empty($module['docurl'])) {
      $url = bebop_split_url($module['docurl']);

      $tmp = html::em('th', 'Документация:');
      $tmp .= html::em('td', l($module['docurl'], $url['host']));

      $output .= html::em('tr', $tmp);
    }

    $output .= '</table>';

    return $output;
  }

  private static function onGetSearch(Context $ctx)
  {
    $form = new Form(array(
      'title' => 'Поиск документов',
      'action' => '?q=admin.rpc&action=search',
      'class' => 'advSearchForm',
      ));

    $form->addControl(new HiddenControl(array(
      'value' => 'search_from',
      'default' => $ctx->get('from', '?q=admin/content/list'),
      )));

    $form->addControl(new TextLineControl(array(
      'value' => 'search_term',
      'label' => t('Искать текст'),
      'description' => t('Работает для заголовков и индексированных полей.'),
      )));
    $form->addControl(new EnumControl(array(
      'value' => 'search_type',
      'label' => 'Тип документа',
      'options' => TypeNode::getAccessible(),
      'default_label' => t('(любой)'),
      )));
    $form->addControl(new NodeLinkControl(array(
      'value' => 'search_author',
      'label' => 'Автор',
      'values' => 'user.name',
      'default_label' => t('(любой)'),
      )));
    $form->addControl(new SectionControl(array(
      'value' => 'search_tags',
      'label' => 'В разделе',
      'default_label' => t('(в любом)'),
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'search_tags_recurse',
      'label' => t('и в подразделах'),
      'default' => true,
      )));
    $form->addControl(new SubmitControl(array(
      'text' => t('Найти'),
      )));

    $output = $form->getHTML(array());

    bebop_on_json(array('html' => $output));

    return $output;
  }

  /**
   * Проверка доступа к CMS.
   *
   * Если пользователь анонимен и возможен автоматический вход — логинит его
   * прозрачно; если доступа совсем нет — кидает исключение.
   */
  private static function checkAccessAndAutoLogin(Context $ctx)
  {
    if (!mcms::user()->id) {
      if (null === $ctx->get('noautologin')) {
        try {
          $node = Node::load(array(
            'class' => 'user',
            'name' => 'cms-bugs@molinos.ru',
            ));

          if (empty($node->password)) {
            mcms::session('uid', $node->id);
            $ctx->redirect('?q=admin&noautologin=1');
          }
        } catch (ObjectNotFoundException $e) {
        }

        $result = bebop_render_object('page', 'login', 'lib/modules/admin');

        if (false === $result)
          throw new RuntimeException(t('Не удалось вывести страницу для входа '
            .'в административный интерфейс, система повреждена.'));
        else
          return $result;
      }
    }

    if (!count(mcms::user()->getAccess('u') + mcms::user()->getAccess('c')))
      throw new ForbiddenException(t('У вас нет доступа '
        .'к администрированию сайта.'));
  }

  private static function getPage(array $data)
  {
    $data['base'] = mcms::path();

    $output = bebop_render_object('page', 'admin', 'admin', $data);

    return $output;
  }

  /**
   * Изменение списка активных модулей.
   */
  public static function rpc_modenable(Context $ctx)
  {
    $failed = $ok = array();
    $enabled = $ctx->post('modules');

    // Удаляем отключенные модули.
    foreach (modman::getLocalModules() as $name => $info)
      if (!in_array($name, $enabled))
        modman::uninstall($name);

    // Загружаем отсутствующие модули.
    foreach (modman::getAllModules() as $name => $info) {
      if (in_array($name, $enabled))
        if (!modman::install($name))
          $failed[] = $name;
    }

    $next = new url($ctx->get('destination', '?q=admin'));
    $next->setarg('status.failed', implode(',', $failed));

    $config = Config::getInstance();
    $config->set('runtime.modules', array_diff($enabled, $failed));
    $config->write();

    Loader::rebuild();
    Structure::getInstance()->rebuild();

    mcms::flush();
    mcms::flush(mcms::FLUSH_NOW);

    return new Redirect($next->string());
  }
}
