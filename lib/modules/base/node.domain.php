<?php
/**
 * Тип документа «domain» — домен, типовая страница.
 *
 * @package mod_base
 * @subpackage Types
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Тип документа «domain» — домен, типовая страница.
 *
 * Ноды этого типа описывают домен или типовую страницу,
 * в зависимости от наличия родителя.  Если поле "parent_id"
 * пусто — это домен, не пусто — страница.
 *
 * @package mod_base
 * @subpackage Types
 */
class DomainNode extends Node implements iContentType
{
  private $oldname;

  /**
   * Обработка изменения имени домена.
   *
   * Обновляются ссылки во всех доменах, ссылающихся на этот.
   */
  public function __set($key, $val)
  {
    if ('name' == $key and empty($this->oldname))
      $this->oldname = $this->name;
    return parent::__set($key, $val);
  }

  /**
   * Сохранение объекта.
   *
   * Перед самим сохранением проверяет имя на уникальность в пределах
   * родителя.  Также выполняет дополнительную обработку списка алиасов.
   *
   * @return Node сохранённый объект.
   */
  public function save()
  {
    parent::checkUnique('name', t('Страница с таким именем уже существует.'), array('parent_id' => $this->parent_id));

    if (!empty($this->oldname) and $this->oldname != $this->name) {
      foreach (Node::find(array('class' => 'domain')) as $node)
        if ($node->redirect == $this->oldname) {
          $node->redirect = $this->name;
          $node->save();
        }
    }

    return parent::save();
  }

  public function duplicate($parent = null)
  {
    if (null === $this->parent_id)
      $this->name = rand() .'.'. preg_replace('/^[0-9]+\./', '', $this->name);
    else
      $this->name = preg_replace('/_[0-9]+$/', '', $this->name) .'_'. rand();

    parent::duplicate($parent);
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
      if (empty($root->redirect)) {
        foreach ($root->getChildren('flat') as $em)
          if ($dev or $em['theme'] != 'admin')
            $result[] = $em;
      }
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

  // РАБОТА С ФОРМАМИ.

  public function formGet($simple = true)
  {
    if (!empty($this->redirect) or (empty($this->id) and !empty($_GET['alias'])))
      return $this->formGetAlias();

    $form = parent::formGet($simple);
    $user = mcms::user();

    $form->hideControl('node_content_redirect');

    if ($this->parent_id)
      $form->hideControl('node_content_robots');

    if (empty($this->parent_id))
      $form->title = $this->id ? t('Свойства домена') : t('Добавление домена');
    else
      $form->title = $this->id ? t('Свойства страницы') : t('Добавление страницы');

    if ($user->hasAccess('u', 'widget') and !$simple) {
      if (null !== ($tab = $this->formGetWidgets()))
        $form->addControl($tab);
    }

    $this->fixThemes($form);

    return $form;
  }

  private function formGetAlias()
  {
    $target = $this->id
      ? $this->redirect
      : Node::load($_GET['alias'])->name;

    $form = new Form(array(
      'action' => parent::formGet()->action,
      'title' => $this->id
        ? t('Редактирование алиаса')
        : t('Добавление алиаса'),
      ));

    $form->addControl(new TextLineControl(array(
      'value' => 'node_content_name',
      'label' => t('Доменное имя'),
      'default' => 'www.'. $target,
      )));

    $form->addControl(new TextLineControl(array(
      'value' => 'node_content_redirect',
      'label' => t('Перенаправлять на'),
      'default' => $target,
      )));

    $form->addControl(new EnumControl(array(
      'value' => 'node_content_defaultsection',
      'label' => t('Раздел по умолчанию'),
      'description' => t('При указании раздела перенаправление '
        .'выполняться не будет. Страница будет открываться по '
        .'введённому адресу, но будет использоваться домен, '
        .'на который настроено перенаправление. Базовый раздел '
        .'этого домена будет подменён.'),
      )));

    $form->addControl(new SubmitControl());

    return $form;
  }

  private function formGetWidgets()
  {
    $options = array();

    foreach (Node::find(array('class' => 'widget', '#sort' => array('name' => 'ASC'))) as $w) {
      $name = t('%title (<a href=\'@edit\'>изменить</a>)', array(
        '%title' => $w->title,
        '@edit' => "?q=admin&mode=edit&cgroup=structure&id={$w->id}&destination=CURRENT",
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
      $data['node_content_robots'] = "User-agent: *\n"
        ."Disallow: /lib\n"
        ."Disallow: /themes";
    }

    $data['node_content_defaultsection:options'] = TagNode::getTags('select');
    $data['node_content_params:options'] = array(
      '' => '(без параметров)',
      'sec' => '/раздел',
      'sec+doc' => '/раздел/документ',
      'doc' => '/документ',
      );

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
      // Страница с ошибками.
      $node = Node::create('domain', array(
        'parent_id' => $this->id,
        'name' => 'errors',
        'title' => t('Обработчики ошибок'),
        'theme' => $this->theme,
        'lang' => $this->lang,
        'published' => true,
        ))->save();

      Node::create('domain', array(
        'parent_id' => $node,
        'name' => '403',
        'title' => 'Forbidden',
        'theme' => $this->theme,
        'published' => true,
        ))->save();

      Node::create('domain', array(
        'parent_id' => $node,
        'name' => '404',
        'title' => 'Not Found',
        'theme' => $this->theme,
        'published' => true,
        ))->save();

      Node::create('domain', array(
        'parent_id' => $node,
        'name' => '500',
        'title' => 'Internal Server Error',
        'theme' => $this->theme,
        'published' => true,
        ))->save();

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

  /**
   * Рендеринг страницы.
   *
   * Загружает прикреплённые к странице виджеты, загружает результаты их работы
   * из кэша, отсутствующие в кэше виджеты рендерятся на лету.
   *
   * @return string содержимое для выдачи клиенту.
   */
  public function render(Context $ctx)
  {
    $ctx->theme = $this->theme;

    if (!empty($this->defaultsection))
      $ctx->root = $this->defaultsection;

    $data = array(
      'base' => $ctx->url()->getBase($ctx),
      'lang' => $ctx->getLang(),
      'page' => $this->getRaw(),
      'widgets' => $this->getWidgetData($ctx),
      );

    $html = bebop_render_object('page', $this->template_name,
      $this->theme, $data);

    $content_type = empty($this->content_type)
      ? 'text/html'
      : $this->content_type;

    switch ($ctx->debug()) {
    case 'page':
      mcms::debug(array(
        'template_name' => $this->template_name,
        'data' => $data,
        ));

    case 'widget':
      if (null === $ctx->get('widget'))
        mcms::debug('Usage: ?debug=widget&widget=name');
      elseif (!array_key_exists($name = $ctx->get('widget'), $data['widgets']))
        mcms::debug(sprintf('Widget %s does not exist.', $name));
    }

    header('Content-Type: '. $content_type .'; charset=utf-8');

    if (null !== ($name = $ctx->get('widget'))) {
      if (!array_key_exists($name, $data['widgets']))
        throw new PageNotFoundException(t('Нет такого виджета '
          .'на этой странице.'));
      else
        return $data['widgets'][$name];
    }

    return $html;
  }

  /**
   * Рендеринг отдельных виджетов.
   *
   * Возвращает массив отрендеренных виджетов, name => html.
   */
  private function getWidgetData(Context $ctx)
  {
    // Получаем массив всех виджетов, готовых к рендерингу.
    $objects = $this->getWidgetObjects($ctx);

    // Получаем кэшированные виджеты.
    $result = $this->getCachedWidgets($ctx, $objects);

    // Добавляем виджеты, отсутствующие в кэше, кэширует их.
    $this->getNonCachedWidgets($ctx, $objects, $result);

    return $result;
  }

  /**
   * Получение массива виджетов.
   *
   * Возвращает объекты виджетов, прикреплённые к странице и доступные для
   * вывода (некоторые виджеты могут отказаться работать).
   *
   * @return array массив виджетов.
   */
  private function getWidgetObjects(Context $ctx)
  {
    $objects = array();

    $widgets = Node::find(array(
      'class' => 'widget',
      'published' => true,
      'tags' => $this->id,
      ));

    $pick = ('widget' == $ctx->debug())
      ? $ctx->get('widget')
      : null;

    foreach ($widgets as $w) {
      if (null !== $pick and $w->name != $pick)
        continue;

      if (!empty($w->classname) and class_exists($w->classname)) {
        $wo = new $w->classname($w);

        try {
          $tmp = $wo->setContext($ctx->forWidget($w->name));

          if (is_array($tmp))
            $objects[] = $wo;
          elseif (false !== $tmp)
            throw new RuntimeException(t('%class::getRequestOptions() '
              .'должен вернуть массив или false.',
              array('%class' => get_class($wo))));
        } catch (WidgetHaltedException $e) { }
      }
    }

    return $objects;
  }

  /**
   * Получение виджетов из кэша.
   *
   * Загружает из кэша результаты работы виджетов.
   *
   * @return array данные виджетов, name => content.
   */
  private function getCachedWidgets(Context $ctx, array $objects)
  {
    $result = array();

    if (bebop_is_debugger() and $ctx->get('nocache'))
      return $result;

    foreach ($objects as $o) {
      $name = $o->getInstanceName();

      if (null !== ($key = $o->getCacheKey()))
        $result[$name] = $key;
    }

    if (!empty($result)) {
      $data = mcms::db()->getResultsKV('cid', 'data',
        "SELECT `cid`, `data` FROM `node__cache` "
        ."WHERE `cid` IN ('". join("', '", $result) ."')");

      foreach ($result as $k => $v) {
        if (!array_key_exists($v, $data))
          unset($result[$k]);
        else
          $result[$k] = self::strip($data[$v]);
      }
    }

    return $result;
  }

  /**
   * Рендеринг виджетов, отсутствующих в кэше.
   *
   * Возвращает отрендеренные блоки виджетов, не найденных в кэше.  Попутно
   * кэширует их, если это не запрещено конкретными виджетами.
   */
  private function getNonCachedWidgets(Context $ctx, array $objects, array &$result)
  {
    // Данные для включения в кэш.
    $cache = array();

    foreach ($objects as $o) {
      $name = $o->getInstanceName();

      if (!array_key_exists($name, $result)) {
        if ('' !== ($result[$name] = self::strip(strval($o->render())))) {
          if (null !== ($ck = $o->getCacheKey()))
            $cache[$ck] = $result[$name];
        }
      }
    }

    $this->saveWidgetsToCache($ctx, $cache);
  }

  /**
   * Сохраняет в кэше отрендеренные блоки виджетов.
   */
  private function saveWidgetsToCache(Context $ctx, array $data)
  {
    if (!empty($data)) {
      $db = mcms::db();

      $db->beginTransaction();

      foreach ($data as $k => $v) {
        if (is_string($v))
          $db->exec("REPLACE INTO `node__cache` (`cid`, `data`) "
            ."VALUES (?, ?)", array($k, $v));
      }

      $db->commit();
    }
  }

  private static function strip($data)
  {
    return preg_replace('@>[\s\r\n\t]+<@', '><', $data);
  }

  public function getActionLinks()
  {
    $links = parent::getActionLinks();

    if (empty($this->redirect))
      $links['alias'] = array(
        'href' => '?q=admin/structure/create&type=domain'
          .'&alias='. $this->id
          .'&destination=CURRENT',
        'title' => t('Добавить алиас'),
        'icon' => 'link',
        );

    $links['edit']['href'] = '?q=admin/structure/edit/'. $this->id
      .'&destination=CURRENT';

    if (!empty($this->redirect) and array_key_exists('clone', $links))
      unset($links['clone']);

    return $links;
  }
};
