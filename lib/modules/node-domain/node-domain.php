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
      $base = BebopConfig::getInstance()->basedomain;

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
  public function save($clear = true, $forcedrev = null)
  {
    $this->fixAliases();

    if ($this->parent_id === null)
      $this->name = self::getFakeDomainName($this->name);

    if ($this->id === null) {
      try {
        $old = Node::load(array('class' => 'domain', 'name' => $this->name, 'parent_id' => $this->parent_id));
        throw new UserErrorException(t("Объект уже существует"), 400, t("Объект уже существует"), t("Объект с таким именем уже существует, <a href='@editlink'>перейти к редактированию</a>?",
          array('@editlink' => "/admin/node/{$old->id}/edit/?destination=". urlencode(urldecode($_GET['destination'])))));
      } catch (ObjectNotFoundException $e) { }
    }

    parent::save($clear, $forcedrev);
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

    $su = AuthCore::getInstance()->getUser()->hasGroup('CMS Developers');
    $themes = glob($_SERVER['DOCUMENT_ROOT'] .'/themes/'.'*');

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
    $vid = (BEBOP_VERSION == 'BUILDNUMBER') ? t('из SVN') : BEBOP_VERSION;
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
    $data['base'] = $this->base;
    $data['lang'] = $this->language;
    $data['version'] = $this->getVersionId();
    $data['user'] = array(
      'uid' => AuthCore::getInstance()->getUser()->getUid(),
      'name' => AuthCore::getInstance()->getUser()->getName(),
      'groups' => AuthCore::getInstance()->getUser()->getGroups(),
      );
    $data['page'] = $this->getRaw();

    $data['page']['lang'] = $data['page']['language'];

    $output = bebop_render_object('page', $name, $this->theme, $data);

    if (!empty($_GET['smarty_debug']) and bebop_is_debugger())
      $this->content_type = 'text/html';

    $content_type = empty($this->content_type) ? "text/html" : $this->content_type;
    $html_charset = empty($this->html_charset) ? "utf-8" : $this->html_charset;

    header("Expires: -1");
    header("Cache-Control: no-store, no-cache, must-revalidate", true);
    header('Cache-Control: post-check=0, pre-check=0', false);
    header("Pragma: no-cache", true);
    header("HTTP/1.1 {$this->http_code} OK");
    header("Content-Type: {$content_type}; charset={$html_charset}");

    return trim(preg_replace("/^(\xEF\xBB\xBF)/", '', $output));
  }

  // Возвращает путь к шаблону.
  private function getTemplateFile($type = 'page')
  {
    // Здесь будем искать шаблоны.
    $path = "{$_SERVER['DOCUMENT_ROOT']}/themes/{$this->theme}/templates";

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

  public static function getFlatSiteMap($mode = null)
  {
    $dev = AuthCore::getInstance()->getUser()->hasGroup('CMS Developers');

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
    $result = BebopCache::getInstance()->urlmap;

    if (!is_array($result)) {
      $roots = Node::find(array('class' => 'domain', 'parent_id' => null));

      foreach ($roots as $root) {
        $branch = Tagger::getInstance()->getObjectTree($root->id);

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

      BebopCache::getInstance()->urlmap = $result;
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

  // Сохранение фиксированных прав.
  public function setAccess(array $perms, $reset = true)
  {
    parent::setAccess(array(
      'Developers' => array('r', 'u', 'd'),
      'Visitors' => array('r'),
      ), true);
  }

  public function getAccess()
  {
    $data = parent::getAccess();

    if (null === $this->id) {
      $data['Visitors']['r'] = 1;
      $data['Developers']['r'] = 1;
      $data['Developers']['u'] = 1;
      $data['Developers']['d'] = 1;
    }

    return $data;
  }

  // Проверка прав на объект.  Девелоперы всегда всё могут.
  public function checkPermission($perm)
  {
    if (AuthCore::getInstance()->getUser()->hasGroup('Developers'))
      return true;
    return NodeBase::checkPermission($perm);
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
    $user = AuthCore::getInstance()->getUser();

    if ($user->hasGroup('Developers')) {
      if (null !== ($tab = $this->formGetWidgets()))
        $form->addControl($tab);
    }

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

    foreach (Node::find(array('class' => 'widget', '#sort' => array('widget.title' => 'ASC'))) as $w)
      if (substr($w->name, 0, 5) != 'Bebop')
        $options[$w->id] = $w->title;

    $tab = new FieldSetControl(array(
      'name' => 'widgets',
      'label' => t('Виджеты'),
      ));
    $tab->addControl(new SetControl(array(
      'value' => 'node_domain_widgets',
      'label' => 'Задействованные виджеты',
      'description' => t("Вы можете также <a href='@link'>создать новый виджет</a>.",
        array('@link' => '/admin/node/create/?BebopNode.class=widget&destination='. urlencode($_SERVER['REQUEST_URI'] .'#widgets'))),
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
      return "/admin/node/{$this->id}/edit/?destination=". urlencode($_GET['destination']);
    }

    // Объект уже существовал, сохраняем дополнительные свойства.
    else {
      $user = AuthCore::getInstance()->getUser();

      if ($user->hasGroup('Developers'))
        $this->linkSetChildren(empty($data['node_domain_widgets']) ? array() : $data['node_domain_widgets'], 'widget');
    }
  }
};
