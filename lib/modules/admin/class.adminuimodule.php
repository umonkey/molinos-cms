<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminUIModule implements iAdminUI, iRemoteCall
{
  public static function onGet(RequestContext $ctx)
  {
    if (!mcms::user()->id)
      throw new UnauthorizedException();

    if (!count(mcms::user()->getAccess('u')))
      throw new ForbiddenException();

    $result = array();

    $m = $ctx->get('module');
    if (null === ($module = $ctx->get('module')))
      $result['content'] = self::onGetInternal($ctx);

    elseif (!count($classes = mcms::getImplementors('iAdminUI', $module))) {
      throw new PageNotFoundException(null, t('Запрошенный модуль (%name) не поддерживает работу с административным интерфейсом.', array('%name' => $module)));
    }

    elseif (!class_exists($classes[0])) {
      mcms::log(t('Класс %class, используемый админкой, не мог быть загружен.', array('%class' => $classes[0])));
    }

    else {
      $result['content'] = call_user_func_array(array($classes[0], 'onGet'), array($ctx));
    }

    $result['dashboard'] = strval(new AdminMenu());

    $output = self::getPage($result);


    header('Content-Type: text/html; charset=utf-8');
    die($output);
  }

  private static function getPage(array $data)
  {
    $data['base'] = mcms::path();

    $output = bebop_render_object('page', 'admin', 'admin', $data);
    $output .= sprintf('<!-- request time: %s sec. -->', microtime(true) - MCMS_START_TIME);

    return $output;
  }

  private function addTiny(&$output)
  {
    $tmp = array(&$output, Node::create('page', array('content_type' => 'text/html')));
    mcms::invoke_module('tinymce', 'iPageHook', 'hookPage', $tmp);
  }

  private function addCompressor(&$output)
  {
    $tmp = array(&$output, Node::create('page', array('content_type' => 'text/html')));
    mcms::invoke_module('compressor', 'iPageHook', 'hookPage', $tmp);
  }

  private static function onGetInternal(RequestContext $ctx)
  {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $url = bebop_split_url();
      $url['args']['search'] = empty($_POST['search']) ? null : $_POST['search'];
      mcms::redirect($url);
    }

    switch ($mode = $ctx->get('mode', 'status')) {
    case 'search':
    case 'list':
    case 'tree':
    case 'edit':
    case 'create':
    case 'logout':
    case 'status':
    case 'modules':
    case 'drafts':
    case 'exchange':
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

  private static function onGetExchange(RequestContext $ctx)
  {
    $result = $ctx->get('result');
    if ($result=='importok') {
       $resulttext = new  InfoControl(array('text'=>'Импорт прошёл успешно'));
       return $resulttext->getHTML(array());
    }

    $form = new Form(array(
      'title' => t('Экспорт/импорт сайта в формате XML'),
      'description' => t("Необходимо выбрать совершаемое вами действие"),
      'action' => 'exchange.rpc',
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

  private static function onGetEdit(RequestContext $ctx)
  {
    if (null === ($nid = $ctx->get('id')))
      throw new PageNotFoundException();

    $node = Node::load(array(
      'id' => $nid,
      'deleted' => array(0, 1),
      '#recurse' => true
      ));

    $form = $node->formGet(false);
    $form->addClass('tabbed');

    return $form->getHTML($node->formGetData());
  }

  private static function onGetCreate(RequestContext $ctx)
  {
    if (null !== $ctx->get('type')) {
      $node = Node::create($type = $ctx->get('type'), array(
        'parent_id' => $ctx->get('parent'),
        ));

      $form = $node->formGet(false);
      $form->addClass('tabbed');
      $form->addClass("node-{$type}-create-form");
      $form->action = "nodeapi.rpc?action=create&type={$type}&destination=". urlencode($_GET['destination']);

      if ($ctx->get('dictionary')) {
        $form->title = t('Добавление справочника');

        if (null !== ($tmp = $form->findControl('tab_general')))
          $tmp->intro = t('Вы создаёте первый справочник.  Вы сможете использовать его значения в качестве выпадающих списков (для этого надо будет добавить соответствующее поле в нужный <a href=\'@types\'>тип документа</a>).', array('@types' => 'admin/?cgroup=structure&mode=list&preset=schema'));

        $form->replaceControl('node_content_hasfiles', null);
        $form->replaceControl('node_content_notags', null);
        $form->replaceControl('tab_sections', null);
        $form->replaceControl('tab_widgets', null);

        if (null !== ($ctl = $form->findControl('node_content_title')))
          $ctl->label = t('Название справочника');
        if (null !== ($ctl = $form->findControl('node_content_name')))
          $ctl->label = t('Внутреннее имя справочника');

        $form->addControl(new HiddenControl(array(
          'value' => 'node_content_isdictionary',
          'default' => 1,
          )));
      }

      return $form->getHTML($node->formGetData());
    }

    $types = Node::find(array(
      'class' => 'type',
      '-name' => TypeNode::getInternal(),
      ));

    $output = '<dl>';

    foreach ($types as $type) {
      $output .= '<dt>';
      $output .= mcms::html('a', array(
        'href' => "admin?mode=create&type={$type->name}&destination=". urlencode($_GET['destination']),
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
    User::authorize();
    mcms::redirect($_GET['destination']);
  }

  private static function onGetStatus(RequestContext $ctx)
  {
    if ('setpass' == $ctx->get('msg')) {
      return t('<h2>Добро пожаловать в Molinos.CMS!</h2>'
        .'<p>Вы успешно установили систему.  Сейчас она работает в открытом режиме — любой пользователь будет автоматически идентифицирован как разработчик и получит полный набор прав, поэтому вы, скорее всего, первым делом захотите <a href=\'@url\'>установить пароль</a> на эту учётную запись.</p>', array(
        '@url' => 'admin?cgroup=access&mode=edit&id='. mcms::user()->id,
        ));
    }

    $output = self::getUnindexed();

    return $output ? '<h2>Обнаружены проблемы</h2>'. $output : 'Система в порядке.';
  }

  private static function getUnindexed()
  {
    if (null !== ($stat = NodeIndexer::stats()))
      return t('<p>%count объектов нуждаются в индексации.  Они будут проиндексирвоаны при выполнении планировщика, или вы можете <a href=\'@url\'>проиндексировать их вручную</a>.  Пока индексация не будет завершена, сортировка и выборка будут работать некорректно.', array('%count' => $stat['_total'], '@url' => 'admin.rpc?action=reindex'));
  }

  private static function onGetModules(RequestContext $ctx)
  {
    switch ($ctx->get('action')) {
    case 'info':
      return self::onGetModuleInfo($ctx->get('name'));

    case 'config':
      $form = mcms::invoke_module($ctx->get('name'), 'iModuleConfig', 'formGetModuleConfig');

      if (!($form instanceof Form))
        throw new PageNotFoundException();

      $data = array();

      if (false !== ($tmp = mcms::modconf($ctx->get('name'))))
        foreach ($tmp as $k => $v)
          $data['config_'. $k] = $v;

      if (empty($form->title))
        $form->title = t('Настройка модуля %name', array('%name' => $ctx->get('name')));

      $form->action = bebop_combine_url($tmp = array(
        'path' => 'admin.rpc',
        'args' => array(
          'module' => $ctx->get('name'),
          'action' => 'modconf',
          'destination' => $_SERVER['REQUEST_URI'],
          ),
        ), false);

      $form->addControl(new SubmitControl(array(
        'text' => t('Сохранить'),
        )));

      return $form->getHTML($data);
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

      $tmp = mcms::html('th', 'Документация:');
      $tmp .= mcms::html('td', l($module['docurl'], $url['host']));

      $output .= mcms::html('tr', $tmp);
    }

    $output .= '</table>';

    return $output;
  }

  private static function onGetSearch(RequestContext $ctx)
  {
    $form = new Form(array(
      'title' => 'Поиск документов',
      'action' => 'admin.rpc?action=search',
      'class' => 'advSearchForm',
      ));
    $form->addControl(new HiddenControl(array(
      'value' => 'search_from',
      'default' => $ctx->get('from'),
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
      )));
    $form->addControl(new NodeLinkControl(array(
      'value' => 'search_author',
      'label' => 'Автор',
      'values' => 'user.name',
      )));
    $form->addControl(new EnumControl(array(
      'value' => 'search_tags',
      'label' => 'В разделе',
      'options' => TagNode::getTags('select'),
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

  public static function hookRemoteCall(RequestContext $ctx)
  {
    $next = $ctx->get('destination', '/');

    switch ($ctx->get('action')) {
    case 'reload':
      if (file_exists($tmp = mcms::config('tmpdir') .'/.modmap.php'))
        unlink($tmp);

      foreach (glob(mcms::config('tmpdir') .'/.pcache.*') as $tmp)
        unlink($tmp);

      DBCache::getInstance()->flush(false);
      DBCache::getInstance()->flush(true);

      mcms::flush();
      mcms::flush(mcms::FLUSH_NOW);
      break;

    case 'reindex':
      if (NodeIndexer::run())
        $next = 'admin.rpc?action=reindex';
      else
        $next = 'admin';
      break;

    case 'modlist':
      self::hookModList($ctx);
      break;

    case 'modconf':
      self::hookModConf($ctx);
      die(mcms::redirect('admin?cgroup=structure&mode=modules'));

    case 'search':
      $terms = array();

      foreach (array('term' => '', 'author' => 'uid:', 'type' => 'class:') as $k => $v)
        if (null !== ($tmp = $ctx->post('search_'. $k)) and !empty($tmp))
          $terms[] = $v . $tmp;

      if ($tmp = $ctx->post('search_tags')) {
        if ($ctx->post('search_tags_recurse')) {
          if (is_array($ids = mcms::db()->getResultsV('id', 'SELECT `n`.`id` FROM `node` `n`, `node` `parent` WHERE `n`.`class` = \'tag\' AND `n`.`deleted` = 0 AND `parent`.`id` = :tid AND `n`.`left` >= `parent`.`left` AND `n`.`right` <= `parent`.`right`', array(':tid' => $tmp))))
            $tmp = join(',', $ids);
        }
        $terms[] = 'tags:'. $tmp;
      }

      $url = bebop_split_url($ctx->post('search_from'));
      $url['args']['search'] = join(' ', $terms);

      $next = bebop_combine_url($url, false);

      break;
    }

    mcms::redirect($next);
  }

  private static function hookModList(RequestContext $ctx)
  {
    if ('POST' != $_SERVER['REQUEST_METHOD'])
      throw new PageNotFoundException();

    mcms::user()->checkAccess('u', 'moduleinfo');

    mcms::enableModules($ctx->post('selected', array()));

    TypeNode::install();
  }

  private static function hookModConf(RequestContext $ctx)
  {
    $conf = array();

    mcms::user()->checkAccess('u', 'moduleinfo');

    foreach ($ctx->post as $k => $v) {
      if (substr($k, 0, 7) == 'config_' and !empty($v))
        $conf[substr($k, 7)] = $v;
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
};
