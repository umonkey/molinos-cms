<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

abstract class Widget implements iWidget
{
  // Информация о виджете, ключи: name, title, description.
  protected $info = array();

  // Список имён групп, необходимых для работы с виджетом.
  protected $groups = array();

  // Карта параметров.  Используется большинством виджетов с несложной
  // валидацией параметров (для более сложной нужен собственный обработчик
  // метода getViewOptions()).
  // Формат: имя_параметра => array(значение => array(имена дополнительных параметров)).
  protected $arguments = array();

  // Информация о виджете.
  protected $me = null;

  // Информация о пользователе.
  protected $user = null;

  // Сохраняем контекст, в котором работает виджет.
  protected $ctx = null;

  // Сюда складываются опции после парсинга.
  protected $options = array();

  // Базовая инициализация, ничего не делает.
  public function __construct(Node $node)
  {
    $this->me = $node;
    $this->user = mcms::user();

    if (null === $this->me->config)
      $this->me->config = array();
  }

  // Возвращает имя виджета.
  public function getInstanceName()
  {
    return $this->me->name;
  }

  // Возвращает имя виджета.
  public function getClassName()
  {
    return $this->me->classname;
  }

  // Возвращает описание виджета.
  public static function getInfo($class)
  {
    if (!mcms::class_exists($class))
      return array();
    return call_user_func(array($class, 'getWidgetInfo'));
  }

  public function formHookConfigData(array &$data)
  {
  }

  public function formHookConfigSaved()
  {
  }

  public static function formGetConfig()
  {
    $form = new FieldSetControl(array(
      'name' => 'config',
      'label' => t('Настройка'),
      ));

    return $form;
  }

  public function checkRequiredGroups()
  {
    if (!empty($this->groups) and is_array($this->groups))
      foreach ($this->groups as $group)
        if ($this->user->hasGroup($group))
          return true;
    return empty($this->groups);
  }

  // Препроцессор параметров.  Используется только в простых случаях,
  // если параметризация виджета целиком основана на запросе пользователя.
  // Если у виджета есть собственные настройки, которые переопределяют
  // или дополняют переданные извне параметры, нужно переопределить
  // этот метод (можно в начале вызвать родительский).
  public function getRequestOptions(RequestContext $ctx)
  {
    if ($this->onlyathome and null !== $ctx->section_id)
      throw new WidgetHaltedException();

    if ($this->onlyiflast and null !== $ctx->document_id)
      throw new WidgetHaltedException();

    $options = array();
    $options['groups'] = array_keys($this->user->getGroups());
    $options['__cleanurls'] = mcms::config('cleanurls');

    sort($options['groups']);

    $this->ctx = $ctx;

    return $options;
  }

  // Проверяем, нужно ли отображать этот виджет.
  public function checkPreConditions(array $apath, array $get)
  {
    // Виджет должен отображаться только в хвосте.
    if (!empty($this->config['onlyiflast']) and !empty($this->config['filter']) and !empty($apath[$this->config['filter']]))
      return false;

    if (!empty($this->config['onlyifasked']) and empty($get))
      return false;

    if (!$this->checkRequiredGroups())
      return false;

    return true;
  }

  // Перегружаем получение ключа кэша.
  public final function getCacheKey(RequestContext $ctx)
  {
    if (!$this->checkPreConditions($ctx->apath, $ctx->get))
      throw new WidgetHaltedException();

    $options = $this->getRequestOptions($ctx);

    $url = new url();

    if ('admin' == trim($url->path, '/') and empty($options['groups']))
      $options['groups'] = $this->user->getGroups();

    if (!empty($options['#nocache']))
      return null;

    $options['#instance'] = $this->getInstanceName();

    return md5(serialize($options));
  }

  // Рисуем постраничную листалку.
  protected function getPager($total, $current, $limit = null, $default = 1)
  {
    $result = array();

    if ($limit === null)
      $limit = $this->limit;

    if (empty($limit))
      return null;

    $result['documents'] = $total;
    $result['pages'] = $pages = ceil($total / $limit);
    $result['perpage'] = intval($limit);
    $result['current'] = $current;

    if ('last' == $current)
      $result['current'] = $current = $pages;

    if ('last' == $default)
      $default = $pages;

    if ($pages > 0) {
      // Немного валидации.
      if ($current > $pages or $current <= 0)
        throw new UserErrorException("Страница не найдена", 404, "Страница не найдена", "Вы обратились к странице {$current} списка, содержащего {$pages} страниц.&nbsp; Это недопустимо.");

      // С какой страницы начинаем список?
      $beg = max(1, $current - 5);
      // На какой заканчиваем?
      $end = min($pages, $current + 5);

      // Расщеплённый текущий урл.
      $url = bebop_split_url();

      for ($i = $beg; $i <= $end; $i++) {
        $url['args'][$this->getInstanceName()]['page'] = ($i == $default) ? '' : $i;
        $result['list'][$i] = ($i == $current) ? '' : bebop_combine_url($url);
      }

      if (!empty($result['list'][$current - 1]))
        $result['prev'] = $result['list'][$current - 1];
      if (!empty($result['list'][$current + 1]))
        $result['next'] = $result['list'][$current + 1];
    }

    return $result;
  }

  public function onPost(array $options, array $post, array $files)
  {
    if (empty($post))
      throw new WidgetHaltedException();
  }

  // Вызывает метод в соответствии с запросом.
  protected final function dispatch(array $params, array &$options)
  {
    $method = 'on';

    array_unshift($params, $_SERVER['REQUEST_METHOD']);

    foreach ($params as $part)
      $method .= ucfirst(strtolower($part));

    if ($method != 'onGet' and method_exists($this, $method))
      return $this->$method($options);

    mcms::debug("No handler {$method} in class ". get_class($this));
    throw new PageNotFoundException();
  }

  // Форматирование.
  public final function render($page, $args, $class = null)
  {
    $args['instance'] = $this->getInstanceName();
    $args['lang'] = $page->language;

    $output = bebop_render_object('widget', $this->getInstanceName(), $page->theme, $args, get_class($this));

    if ((null === $output) and !empty($args['html']) and is_string($args['html']))
      $output = $args['html'];

    return $output;
  }

  // Чтение конфигурации.
  public function __get($key)
  {
    if (isset($this->me->config) and array_key_exists($key, $this->me->config))
      return $this->me->config[$key];
    return null;
  }

  public function __set($key, $value)
  {
    $this->me->config[$key] = $value;
  }

  public function __isset($key)
  {
    return isset($this->me->config) and array_key_exists($key, $this->me->config);
  }

  protected function emitNotFound($description = null)
  {
    throw new PageNotFoundException(null, $description);
  }

  // РАБОТА С ФОРМАМИ
  // Документация: http://code.google.com/p/molinos-cms/wiki/Widget

  protected function formRender($id, array $data = null)
  {
    if (null === ($form = $this->formGet($id)))
      return null;

    if (null === $data)
      $data = $this->formGetData($id);

    $form->id = $id;

    if (!($form instanceof Form))
      throw new InvalidArgumentException(t("Значение, полученное от метода formGet(%id) виджета %class не является формой.", array('%id' => $id, '%class' => get_class($this))));

    $form->addControl(new HiddenControl(array('value' => 'form_id')));
    $data['form_id'] = $id;

    $form->addControl(new HiddenControl(array('value' => 'form_handler')));
    $data['form_handler'] = $this->getInstanceName();

    if (null === ($form->findControl('destination')))
      $form->addControl(new HiddenControl(array(
        'value' => 'destination',
        )));

    $html = $form->getHTML($data);

    if (!empty($html) and null !== $id) {
      $html = "<div id='form-{$id}-wrapper'>{$html}</div>";
    }

    return $html;
  }

  public function formGet($id)
  {
    return null;
  }

  public function formProcess($id, array $data)
  {
    mcms::debug("Unhandled form {$id} in class ". get_class($this) .", data follows.", $data);
  }

  // Проверяет, является ли документ допустимым для этого виджета.
  protected function checkDocType(Node $node)
  {
    $types = $this->me->linkListParents('type', true);

    $schema = TypeNode::getSchema($node->class);

    if (!empty($types) and !in_array($schema['id'], (array)$types))
      throw new PageNotFoundException();

    return true;
  }
};
