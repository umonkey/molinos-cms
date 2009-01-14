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

    $result = '';

    if (null === ($module = $ctx->get('module')))
      $result .= html::em('content', self::onGetInternal($ctx));

    elseif (!count($classes = Loader::getImplementors('iAdminUI', $module))) {
      throw new PageNotFoundException(t('Запрошенный модуль (%name) '
        .'не поддерживает работу с административным интерфейсом.',
          array('%name' => $module)));
    }

    elseif (!class_exists($classes[0])) {
      mcms::flog(t('Класс %class, используемый админкой, не мог быть загружен.', array('%class' => $classes[0])));
    }

    else {
      $result .= html::em('content',
        call_user_func_array(array($classes[0], 'onGet'), array($ctx)));
    }

    $am = new AdminMenu();
    $result .= $am->getXML();

    return self::getPage($ctx, array(
      'base' => $ctx->url()->getBase(),
      'content' => $result,
      ));
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
    case 'drafts':
    case 'exchange':
    case 'trash':
    case '404':
    case 'addremove':
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

    return $form->getXML($node);
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

      return $form->getXML($node);
    }

    $types = Node::find(array(
      'class' => 'type',
      '-name' => TypeNode::getInternal(),
      ));

    $output = '';
    $names = array();

    foreach ($types as $type) {
      if (mcms::user()->hasAccess('c', $type->name)) {
        $output .= $type->getXML('type');
        $names[] = $type->name;
      }
    }

    if (1 == count($names))
      $ctx->redirect("?q=admin/content/create&type={$names[0]}&destination="
        . urlencode($ctx->get('destination')));

    return html::em('typechooser', array(
      'destination' => $ctx->get('destination'),
      ), $output);
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

        $result = template::render(dirname(__FILE__), 'page', 'login');

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

  private static function getPage(Context $ctx, array $data)
  {
    if (isset($ctx->theme)) {
      $theme = $ctx->theme;

      if (file_exists($tmp = str_replace('.xsl', '.css', $theme)))
        $ctx->addExtra('style', $tmp);
      if (file_exists($tmp = str_replace('.xsl', '.js', $theme)))
        $ctx->addExtra('script', $tmp);
    } else {
      $theme = os::path('lib', 'modules', 'admin', 'template.xsl');
    }

    $data['content'] .= $ctx->getExtrasXML();
    $data['content'] .= self::getToolBar();

    $data['content'] .= mcms::getSignatureXML($ctx);

    $xml = '<?xml version="1.0" encoding="utf-8"?>';
    $xml .= html::em('page', array(
      'base' => $data['base'],
      'prefix' => 'lib/modules/admin',
      'url' => $_SERVER['REQUEST_URI'],
      'urlEncoded' => urlencode($_SERVER['REQUEST_URI']),
      'cgroup' => self::getCGroup($ctx),
      ), $data['content']);

    $output = xslt::transform($xml, $theme);

    return $output;
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
        . mcms::user()->id
        . '&destination=CURRENT',
      'title' => t('Редактирование профиля'),
      ), mcms::user()->getName());
    $toolbar .= html::em('a', array(
      'class' => 'home',
      'href' => '?q=admin',
      'title' => t('Вернуться к началу'),
      ));
    $toolbar .= html::em('a', array(
      'class' => 'reload',
      'href' => '?q=admin.rpc&action=reload&flush=1&destination=CURRENT',
      'title' => t('Перезагрузка'),
      ));
    $toolbar .= html::em('a', array(
      'class' => 'exit',
      'href' => '?q=base.rpc&action=logout&from='
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

    return html::em('toolbar', $toolbar);
  }
}
