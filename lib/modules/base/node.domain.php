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

    $form->hideControl('redirect');

    if ($this->parent_id)
      $form->hideControl('robots');

    if (empty($this->parent_id))
      $form->title = $this->id ? t('Свойства домена') : t('Добавление домена');
    else
      $form->title = $this->id ? t('Свойства страницы') : t('Добавление страницы');

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
      'value' => 'name',
      'label' => t('Доменное имя'),
      'default' => 'www.'. $target,
      )));

    $form->addControl(new TextLineControl(array(
      'value' => 'redirect',
      'label' => t('Перенаправлять на'),
      'default' => $target,
      )));

    $form->addControl(new SectionControl(array(
      'value' => 'defaultsection',
      'label' => t('Изменить основной раздел на'),
      'default_label' => t('(не изменять)'),
      'store' => true,
      )));

    $form->addControl(new EmailControl(array(
      'value' => 'moderatoremail',
      'label' => t('Адреса модераторов'),
      'description' => t('Список адресов (через запятую), на которые отправляются сообщения о создании документов пользователями, у которых нет прав на публикацию документов. Если не заполнено — используются адреса, указанные в основном домене.'),
      )));

    $form->addControl(new SubmitControl());

    return $form;
  }

  public function formProcess(array $data)
  {
    $isnew = (null === $this->id);

    if ($data['page_type'] == 'domain')
      $data['parent_id'] = null;

    // Специальная обработка редиректов, которые не укладываются в схему.
    if (!empty($data['redirect'])) {
      foreach (array('name', 'redirect', 'moderatoremail', 'defaultsection') as $k)
        $this->$k = array_key_exists($k, $data)
          ? $data[$k]
          : null;
    } else {
      parent::formProcess($data);
    }

    // Если это — новый домен, редиректим на его редактирование.
    if ($isnew and empty($this->parent_id)) {
      if (empty($this->redirect)) {
        // Если это — не алиас, добавляем страницы для обработки ошибок.
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
      }
    }

    return $this;
  }

  // Формирует выпадающий список с именами доступных шкур.
  private function fixThemes(Form &$form)
  {
    if (null !== ($tmp = $form->findControl('theme'))) {
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
      if (!empty($data['page']['children']))
        unset($data['page']['children']);
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

    mcms::profile('start', 'getWidgetObjects()');

    $widgets = Node::find(array(
      'class' => 'widget',
      'published' => true,
      'tags' => $this->id,
      '#permcheck' => 'r',
      ));

    $pick = ($ctx->debug('widget') or $ctx->debug('profile'))
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

    mcms::profile('stop', 'getWidgetObjects()');

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

    if ($ctx->debug('widget'))
      return $result;

    mcms::profile('start', 'getCachedWidgets()');

    foreach ($objects as $o) {
      $name = $o->getInstanceName();

      if (null !== ($key = $o->getCacheKey()))
        $result[$name] = $key;
    }

    if (!empty($result)) {
      foreach ($result as $k => $v) {
        if (!is_array($c = mcms::cache('widget:' . $v)))
          unset($result[$k]);
        else {
          mcms::flog('cache', $v . ': read from cache');
          $result[$k] = self::strip($c['content']);

          if (!empty($c['extras']))
            foreach ($c['extras'] as $k => $v)
              mcms::extras($k, $v);
        }
      }
    }

    mcms::profile('stop', 'getCachedWidgets()');

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

    mcms::profile('start', 'getNonCachedWidgets()');

    $extras = $oxtras = mcms::get_extras();

    foreach ($objects as $o) {
      $name = $o->getInstanceName();

      mcms::profile('start', 'widget:' . $name);

      if (!array_key_exists($name, $result)) {
        $result[$name] = self::strip(strval($o->render()));

        if (count($e = mcms::get_extras()))
          $extras += $e;

        if ('' !== ($result[$name])) {
          if (null !== ($ck = $o->getCacheKey())) {
            $cache[$ck] = array(
              'content' => $result[$name],
              'extras' => $e,
              );
          }
        }
      }

      mcms::profile('stop', 'widget:' . $name);
    }

    mcms::profile('stop', 'getNonCachedWidgets()');

    $this->saveWidgetsToCache($ctx, $cache);

    mcms::set_extras($extras);
  }

  /**
   * Сохраняет в кэше отрендеренные блоки виджетов.
   */
  private function saveWidgetsToCache(Context $ctx, array $data)
  {
    if (!empty($data))
      foreach ($data as $k => $v)
        mcms::cache('widget:' . $k, $v);
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

  public function schema()
  {
    $schema = parent::schema();

    // Устаревшие поля
    if ($this->id)
      unset($schema['parent_id']);
    unset($schema['aliases']);

    return $schema;
  }

  protected function getDefaultSchema()
  {
    return array(
      'name' => array(
        'type' => 'TextLineControl',
        'label' => t('Имя домена'),
        'required' => true,
        ),
      'title' => array(
        'type' => 'TextLineControl',
        'label' => t('Заголовок'),
        'required' => false,
        ),
      'parent_id' => array(
        'type' => 'EnumControl',
        'label' => t('Родительский объект'),
        'volatile' => true,
        ),
      'language' => array(
        'type' => 'EnumControl',
        'label' => t('Язык'),
        'description' => t('Язык для этой страницы, используется только шаблонами.'),
        'required' => true,
        'volatile' => true,
        'options' => array(
          'ru' => t('русский'),
          'en' => t('английский'),
          'de' => t('немецкий'),
          ),
        ),
      'theme' => array(
        'type' => 'EnumControl',
        'label' => t('Шкура'),
        'description' => t('Имя папки с шаблонами для этой страницы.'),
        'volatile' => true,
        ),
      'content_type' => array(
        'type' => 'EnumControl',
        'label' => 'Тип контента',
        'required' => true,
        'volatile' => true,
        'options' => array(
          'text/html' => 'HTML',
          'text/xml' => 'XML',
          ),
        ),
      'params' => array(
        'type' => 'EnumControl',
        'label' => 'Разметка параметров',
        'required' => false,
        'volatile' => true,
        'default_label' => t('(без параметров)'),
        'options' => array(
          'sec+doc' => '/раздел/документ/',
          'sec' => '/раздел/',
          'doc' => '/документ/',
          ),
        ),
      'widgets' => array(
        'type' => 'SetControl',
        'label' => t('Виджеты'),
        'dictionary' => 'widget',
        'field' => 'title',
        'group' => t('Виджеты'),
        'volatile' => true,
        ),
      'http_code' => array(
        'type' => 'EnumControl',
        'label' => t('HTTP код'),
        'default' => 200,
        'required' => true,
        'volatile' => true,
        'options' => array(
          200 => '200 OK',
          403 => '403 Forbidden',
          404 => '404 Not Found',
          500 => '500 Internal Server Error',
          ),
        ),
      'defaultsection' => array(
        'type' => 'SectionControl',
        'label' => t('Основной раздел'),
        'volatile' => true,
        ),
      'moderatoremail' => array(
        'type' => 'EmailControl',
        'label' => t('Адрес модератора'),
        'volatile' => true,
        'description' => t('Список адресов (через запятую), на которые отправляются сообщения о создании документов пользователями, у которых нет прав на публикацию документов.'),
        ),
      );
  }
};
