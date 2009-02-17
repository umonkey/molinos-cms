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

    $su = Context::last()->user->hasAccess('u', 'domain');
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
      $dev = Context::last()->user->hasAccess('u', 'domain');

    $result = array();

    foreach ($roots = Node::find(Context::last()->db, array('class' => 'domain', 'parent_id' => null)) as $root) {
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
    $user = Context::last()->user;

    $form->hideControl('redirect');

    if ($this->parent_id)
      $form->hideControl('robots');

    $this->fixThemes($form);

    return $form;
  }

  public function getFormTitle()
  {
    if (!$this->id)
      return $this->parent_id
        ? t('Добавление новой страницы')
        : t('Добавление нового домена');

    return $this->parent_id
      ? t('Настройка страницы «%name»', array('%name' => $this->name))
      : t('Настройка домена «%name»', array('%name' => $this->name));
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
    $oldname = $this->name;
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

    // При изменении имени домена обновляем все ссылки.
    if (!empty($oldname) and $oldname != $this->name) {
      foreach (Node::find($this->getDB(), array('class' => 'domain')) as $node)
        if ($node->redirect == $oldname) {
          $node->redirect = $this->name;
          $node->save();
        }
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

  private static function strip($data)
  {
    return preg_replace('@>[\s\r\n\t]+<@', '><', $data);
  }

  public function getActionLinks()
  {
    $links = parent::getActionLinks();

    if (empty($this->redirect))
      $links['alias'] = array(
        'href' => '?q=admin.rpc&action=create&cgroup=structure&type=domain'
          .'&alias='. $this->id
          .'&destination=CURRENT',
        'title' => t('Добавить алиас'),
        'icon' => 'link',
        );

    $links['edit']['href'] = '?q=admin.rpc&action=edit&cgroup=structure&node='. $this->id
      .'&destination=CURRENT';

    if (!empty($this->redirect) and array_key_exists('clone', $links))
      unset($links['clone']);

    return $links;
  }

  public static function getDefaultSchema()
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
        'default_label' => t('(не выбрана)'),
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
      'defaultsection' => array(
        'type' => 'SectionControl',
        'label' => t('Основной раздел'),
        'volatile' => true,
        'store' => true,
        ),
      'moderatoremail' => array(
        'type' => 'EmailControl',
        'label' => t('Адрес модератора'),
        'volatile' => true,
        'description' => t('Список адресов (через запятую), на которые отправляются сообщения о создании документов пользователями, у которых нет прав на публикацию документов.'),
        ),
      'aliases' => array(
        'deprecated' => true,
        ),
      'http_code' => array(
        'deprecated' => true,
        ),
      'robots' => array(
        'type' => 'TextAreaControl',
        'label' => t('Инструкции для роботов'),
        'default' => self::getDefaultRobots(),
        ),
      );
  }

  public static function getDefaultRobots()
  {
    return "User-agent: *\n"
      . "Disallow: /themes";
  }

  public function getFormFields()
  {
    $schema = parent::getFormFields();

    // Удаляем устаревшие поля.
    foreach (array('parent_id', 'language', 'content_type', 'html_charset') as $k)
      if (isset($schema[$k]))
        unset($schema[$k]);

    return $schema;
  }
};
