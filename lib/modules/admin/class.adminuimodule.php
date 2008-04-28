<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminUIModule implements iAdminUI, iRemoteCall
{
  public static function onGet(RequestContext $ctx)
  {
    if (!count(mcms::user()->getAccess('u')))
      throw new ForbiddenException();

    if (bebop_is_debugger() and !empty($_GET['flush'])) {
      mcms::flush(false);
      mcms::flush(true);

      bebop_redirect(bebop_split_url());
    }

    $result = array();

    $m = $ctx->get('module');
    if (null === ($module = $ctx->get('module')))
      $result['content'] = self::onGetInternal($ctx);
    elseif (!count($classes = mcms::getImplementors('iAdminUI', $module))) {
      throw new PageNotFoundException();
    }

    else {
      $result['content'] = call_user_func_array(array($classes[0], 'onGet'), array($ctx));
    }

    $tmp = new AdminMenu();
    $result['dashboard'] = $tmp->getHTML();

    $output = bebop_render_object('page', 'admin', 'admin', $result);

    self::addTiny($output);
    self::addCompressor($output);

    header('Content-Type: text/html; charset=utf-8');
    die($output);
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
      'action' => '/exchange.rpc',
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

    $node = Node::load(array('id' => $nid));

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
      $form->action = "/nodeapi.rpc?action=create&type={$type}&destination=". urlencode($_GET['destination']);

      if ($ctx->get('dictionary')) {
        $form->title = t('Добавление справочника');
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

      $form->title = t('Настройка модуля %name', array('%name' => $ctx->get('name')));

      $form->action = bebop_combine_url($tmp = array(
        'path' => '/admin.rpc',
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
      $tmp = bebop_split_url($module['docurl']);

      $output .= '<tr><th>Документация:</th><td><a href=\''. $module['docurl'] .'\'>'
        .str_replace('www.', '', $tmp['host'])
        .'</td></tr>';
    }

    $output .= '</table>';

    return $output;
  }

  public static function hookRemoteCall(RequestContext $ctx)
  {
    switch ($ctx->get('action')) {
    case 'modlist':
      self::hookModList($ctx);
      break;

    case 'modconf':
      self::hookModConf($ctx);
      bebop_redirect('/admin/?cgroup=structure&mode=modules');
      break;
    }

    bebop_redirect($ctx->get('destination', '/'));
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
