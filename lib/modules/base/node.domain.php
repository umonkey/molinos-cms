<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class DomainNode extends Node implements iContentType
{
  public function __construct(array $data)
  {
    if (empty($data['parent_id'])) {
      if (!empty($data['name']))
        $data['name'] = self::getRealDomainName($data['name']);
      if (!empty($data['aliases']) and is_array($data['aliases']))
        foreach ($data['aliases'] as $k => $v)
          $data['aliases'][$k] = self::getRealDomainName($v);
    }

    parent::__construct($data);
  }

  // Возвращает базовое имя домена из конфига.
  private static function getBaseDomainName()
  {
    static $base = null;

    if ($base === null)
      $base = mcms::config('basedomain');

    return $base;
  }

  // Разворачивает имя домена.
  private static function getRealDomainName($name)
  {
    return str_replace('DOMAIN', self::getBaseDomainName(), $name);
  }

  // Сворачивает имя домена.
  private static function getFakeDomainName($name)
  {
    return str_replace(self::getBaseDomainName(), 'DOMAIN', $name);
  }

  // Проверяем на уникальность, разворачиваем basedomain.
  public function save()
  {
    $this->fixAliases();

    if ($this->parent_id === null)
      $this->name = self::getFakeDomainName($this->name);

    parent::checkUnique('name', t('Страница с таким именем уже существует.'), array('parent_id' => $this->parent_id));

    parent::save();
  }

  public function duplicate()
  {
    if (null === $this->parent_id)
      $this->name = rand() .'.'. preg_replace('/^[0-9]+\./', '', $this->name);
    else
      $this->name = preg_replace('/_[0-9]+$/', '', $this->name) .'_'. rand();

    parent::duplicate();
  }

  // Конвертирует алиасы в массив.
  private function fixAliases()
  {
    if ($this->aliases === null or is_array($this->aliases))
      return;

    $aliases = array();

    foreach (preg_split('/[\r\n]+/', $this->aliases) as $alias)
      $aliases[] = self::getFakeDomainName($alias);

    $this->aliases = $aliases;
  }

  // Формирует строку из списка алиасов.
  private function getAliases()
  {
    $aliases = array();

    if (is_array($this->aliases))
      foreach ($this->aliases as $alias)
        $aliases[] = self::getRealDomainName($alias);

    return join("\n", $aliases);
  }

  private function getThemes()
  {
    $result = array();

    $su = mcms::user()->hasAccess('u', 'domain');
    $themes = glob(MCMS_ROOT .'/themes/'.'*');

    if (!empty($themes)) {
      foreach ($themes as $theme) {
        if (is_dir($theme)) {
          $dir = basename($theme);
          if ($dir != 'all' and ($dir != 'admin' or $su))
            $result[$dir] = $dir;
        }
      }
    }

    return $result;
  }

  // Возвращает идентификатор версии.
  private function getVersionId()
  {
    $vid = mcms::version();
    $vid .= ' ['. ini_get('memory_limit');

    // Формируем номер версии.
    switch ($class = get_class(BebopCache::getInstance())) {
    case 'MemCacheD_Cache':
      $vid .= ' +memcache';
      break;
    case 'APC_Cache':
      $vid .= ' +APC';
      break;
    }

    $vid .= ']';

    return $vid;
  }

  // Рендерит страницу с помощью Smarty.
  public function renderSmarty(array $data, $pagetype = 'page')
  {
    $name = $this->parent_id === null ? 'index' : $this->name;

    $data['title'] = $this->title;
    $data['base'] = 'http://'. $_SERVER['HTTP_HOST'] . mcms::path() .'/';

    $data['lang'] = $this->language;
    $data['version'] = $this->getVersionId();
    $data['user'] = array(
      'uid' => mcms::user()->id,
      'name' => mcms::user()->name,
      'groups' => mcms::user()->getGroups(),
      );
    $data['page'] = $this->getRaw();

    if (!empty($data['page']['language']))
      $data['page']['lang'] = $data['page']['language'];

    $output = bebop_render_object('page', $name, $this->theme, $data);

    if (!empty($_GET['smarty_debug']) and bebop_is_debugger())
      $this->content_type = 'text/html';

    return trim(preg_replace("/^(\xEF\xBB\xBF)/", '', $output));
  }

  public function sendHeaders()
  {
    $content_type = empty($this->content_type) ? "text/html" : $this->content_type;
    $html_charset = empty($this->html_charset) ? "utf-8" : $this->html_charset;

    $code = empty($this->http_code) ? 200 : $this->http_code;

    header("Expires: -1");
    header("Cache-Control: no-store, no-cache, must-revalidate", true);
    header('Cache-Control: post-check=0, pre-check=0', false);
    header("Pragma: no-cache", true);
    header("HTTP/1.1 {$code} OK");
    header("Content-Type: {$content_type}; charset={$html_charset}");
  }

  // Возвращает путь к шаблону.
  private function getTemplateFile($type = 'page')
  {
    // Здесь будем искать шаблоны.
    $path = MCMS_ROOT ."/themes/{$this->theme}/templates";

    if ($this->parent_id === null and $type == 'page')
      $this->name = 'index';

    // Пробуем сначала собственный шаблон, затем дефолтный.
    foreach (array(str_replace(' ', '_', $this->name), 'default') as $name) {
        $filename = "{$type}.{$name}.tpl";

        if (is_readable($path .'/'. $filename))
            return $filename;
    }

    if ($this->name == 'index' and $this->parent_id === null)
      throw new UserErrorException("Нет шаблона", 404, "Нет шаблона", "Эту страницу невозможно отобразить, т.к. для неё отсутствует шаблон.&nbsp; Вероятнее всего, сайт находится в разработке.&nbsp; Попробуйте обратиться к странице позднее.");

    throw new InvalidArgumentException("Шаблон для страницы {$this->name} не определён.");
  }

  public static function getFlatSiteMap($mode = null, $admin = false)
  {
    if ($admin)
      $dev = true;
    else
      $dev = mcms::user()->hasAccess('u', 'domain');

    $result = array();

    foreach ($roots = Node::find(array('class' => 'domain', 'parent_id' => null)) as $root) {
      foreach ($root->getChildren('flat') as $em)
        if ($dev or $em['theme'] != 'admin')
          $result[] = $em;
    }

    if ('select' == $mode) {
      $options = array();

      foreach ($result as $k => $v) {
        $options[$v['id']] = str_repeat('&nbsp;', 2 * $v['depth']) . mcms_plain($v['name']);
      }

      return $options;
    }

    return $result;
  }

  // Возвращает полную карту доменов.
  public static function getSiteMap($mode = null)
  {
    $result = mcms::cache('urlmap');

    if (!is_array($result)) {
      $roots = Node::find(array('class' => 'domain', 'parent_id' => null));

      // FIXME: переписать getObjectTree()!

      foreach ($roots as $root) {
        $root->loadChildren();

        $branch = $root->getChildren('nested');

        // Правим имя домена.
        $branch['name'] = self::getRealDomainName($branch['name']);

        // Правим алиасы.
        if (!empty($branch['aliases']) and is_array($branch['aliases'])) {
          foreach ($branch['aliases'] as $k => $v)
            $branch['aliases'][$k] = self::getRealDomainName($v);
        } else {
          $branch['aliases'] = array();
        }

        $result[$branch['id']] = $branch;
      }

      mcms::cache('urlmap', $result);
    }

    return $result;
  }

  // Возвращает базовый домен.
  public static function getBaseDomain()
  {
    $tree = self::getSiteMap();
    $base = self::getBaseDomainName();

    foreach ($tree as $root) {
      if ($root['name'] == $base or in_array($base, $root['aliases']))
        return $root;
    }

    return null;
  }

  // РАБОТА С ФОРМАМИ.

  public function formGet($simple = true)
  {
    if (null === $this->id) {
      $form = new Form(array('title' => t('Добавление домена или страницы')));
      $form->addControl(new InfoControl(array(
        'text' => t('Выберите тип и расположение объекта, после чего будут доступны дополнительные свойства.'),
        )));
      $form->addControl(new TextLineControl(array(
        'value' => 'node_content_name',
        'label' => t('Название'),
        'class' => 'form-title',
        )));
      $form->addControl(new EnumRadioControl(array(
        'value' => 'page_type',
        'label' => t('Тип объекта'),
        'options' => array(
          'domain' => t('Домен'),
          'page' => t('Страница'),
          ),
        )));
      $form->addControl(new TextAreaControl(array(
        'wrapper_id' => 'domain-aliases-wrapper',
        'value' => 'node_content_aliases',
        'label' => t('Дополнительные адреса'),
        'description' => t('Введите дополнительные адреса, на которые должен откликаться этот домен, по одному имени на строку.'),
        )));
      $form->addControl(new EnumControl(array(
        'wrapper_id' => 'domain-parent-wrapper',
        'value' => 'node_content_parent_id',
        'label' => t('Родительский объект'),
        'options' => self::getFlatSiteMap('select'),
        'class' => 'hidden',
        )));
      $form->addControl(new SubmitControl(array(
        'text' => t('Продолжить'),
        )));

      // Добавляем скрытые параметры, чтобы применить значения по умолчанию.
      foreach (array('class', 'language', 'content_type', 'http_code', 'html_charset', 'params') as $key)
        $form->addControl(new HiddenControl(array(
          'value' => 'node_content_'. $key,
          )));

      return $form;
    }

    $form = parent::formGet($simple);
    $user = mcms::user();

    if ($user->hasAccess('u', 'domain')) {
      if (null !== ($tab = $this->formGetWidgets()))
        $form->addControl($tab);
    }

    $this->fixThemes($form);

    if (null === $this->id)
      $form->title = t('Добавление домена или страницы');
    elseif (null === $this->parent_id)
      $form->title = t('Редактирование домена %name', array('%name' => $this->name));
    else
      $form->title = t('Редактирование страницы %name', array('%name' => $this->name));

    return $form;
  }

  private function formGetWidgets()
  {
    $options = array();

    foreach (Node::find(array('class' => 'widget', '#sort' => array('name' => 'ASC'))) as $w) {
      $name = t('%title (<a href=\'@edit\'>изменить</a>)', array(
        '%title' => $w->title,
        '@edit' => "admin?mode=edit&cgroup=structure&id={$w->id}&destination=CURRENT",
        ));

      $options[$w->id] = $name;
    }

    asort($options);

    $tab = new FieldSetControl(array(
      'name' => 'widgets',
      'label' => t('Виджеты'),
      ));
    $tab->addControl(new SetControl(array(
      'value' => 'node_domain_widgets',
      'label' => 'Задействованные виджеты',
      'description' => t("Вы можете также <a href='@link'>создать новый виджет</a>.",
        array('@link' => 'admin/node/create/?BebopNode.class=widget&destination=CURRENT#widgets')),
      'options' => $options,
      )));

    return $tab;
  }

  public function formGetData()
  {
    $data = parent::formGetData();

    if (null === $this->id) {
      $data['node_content_language'] = 'ru';
      $data['node_content_content_type'] = 'text/html';
      $data['node_content_http_code'] = 200;
      $data['node_content_html_charset'] = 'utf-8';
      $data['node_content_params'] = 'sec+doc';
      $data['page_type'] = 'domain';
    } else {
      $data['node_content_defaultsection:options'] = TagNode::getTags('select');
    }

    $data['node_domain_widgets'] = $this->linkListChildren('widget', true);

    return $data;
  }

  public function formProcess(array $data)
  {
    $isnew = (null === $this->id);

    if ($data['page_type'] == 'domain')
      $data['node_content_parent_id'] = null;

    parent::formProcess($data);

    // Если объект новый -- редиректим на его редактирование.
    if ($isnew) {
      return "admin/node/{$this->id}/edit/?destination=". urlencode($_GET['destination']);
    }

    // Объект уже существовал, сохраняем дополнительные свойства.
    else {
      $user = mcms::user();

      if ($user->hasAccess('u', 'domain'))
        $this->linkSetChildren(empty($data['node_domain_widgets']) ? array() : $data['node_domain_widgets'], 'widget');
    }
  }

  public function getDefaultSchema()
  {
    return array(
      'title' => 'Типовая страница',
      'notags' => true,
      'fields' => array(
        'name' => array(
          'label' => 'Имя',
          'type' => 'TextLineControl',
          'required' => true,
          ),
        'title' => array(
          'label' => 'Заголовок',
          'type' => 'TextLineControl',
          'required' => true,
          ),
        'parent_id' => array(
          'label' => 'Родительский объект',
          'type' => 'EnumControl',
          ),
        'aliases' => array(
          'label' => 'Алиасы',
          'type' => 'TextAreaControl',
          'description' => 'Список дополнительных адресов, по которым доступен этот домен.',
          ),
        'language' => array(
          'label' => 'Язык',
          'type' => 'EnumControl',
          'description' => 'Язык для этой страницы, используется только шаблонами.',
          'options' => array(
            'ru' => 'русский',
            'en' => 'английский',
            'de' => 'немецкий',
            ),
          'required' => true,
          ),
        'theme' => array(
          'label' => 'Шкура',
          'type' => 'EnumControl',
          'description' => 'Имя папки с шаблонами для этой страницы.',
          ),
        'content_type' => array(
          'label' => 'Тип контента',
          'type' => 'EnumControl',
          'required' => true,
          'options' => array(
            'text/html' => 'HTML',
            'text/xml' => 'XML',
            ),
          ),
        'params' => array(
          'label' => 'Разметка параметров',
          'type' => 'EnumControl',
          'required' => true,
          'options' => array(
            '' => 'без параметров',
            'sec+doc' => '/раздел/документ/',
            'sec' => '/раздел/',
            'doc' => '/документ/',
            ),
          ),
        'defaultsection' => array(
          'label' => 'Основной раздел',
          'type' => 'EnumControl',
          ),
        ),
      );
  }

  // Формирует выпадающий список с именами доступных шкур.
  private function fixThemes(Form &$form)
  {
    if (null !== ($tmp = $form->findControl('node_content_theme'))) {
      $dirs = array();

      foreach (glob(MCMS_ROOT .'/themes/'.'*', GLOB_ONLYDIR) as $dir) {
        if (!in_array($dir = basename($dir), array('all', 'admin')))
          $dirs[$dir] = $dir;
      }

      if (!empty($this->theme) and !in_array($this->theme, $dirs))
        $dirs[$this->theme] = $this->theme;

      asort($dirs);

      $tmp->options = $dirs;
    }
  }
};
