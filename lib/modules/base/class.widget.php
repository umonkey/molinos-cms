<?php
/**
 * Каркас виджета.
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Каркас виджета.
 *
 * Содержит реализацию наиболее общих функций интерфейса iWidget.
 *
 * @package mod_base
 * @subpackage Widgets
 */
abstract class Widget implements iWidget
{
  /**
   * Информация о виджете, ключи: name, title, description.
   */
  protected $info = array();

  /**
   * Список имён групп, необходимых для работы с виджетом.
   *
   * FIXME: оно кому-нибудь нужно?  "Административных виджетов" давно нет,
   * погаситься можно в getRequestOptions().
   */
  protected $groups = array();

  /**
   * Карта параметров.  Используется большинством виджетов с несложной
   * валидацией параметров (для более сложной нужен собственный обработчик
   * метода getViewOptions()).  Формат: имя_параметра => array(значение =>
   * array(имена дополнительных параметров)).
   */
  protected $arguments = array();

  /**
   * Информация о виджете.
   */
  protected $me = null;

  /**
   * Информация о пользователе.
   *
   * FIXME: оно кому-нибудь нужно?
   */
  protected $user = null;

  /**
   * Сохраняем контекст, в котором работает виджет.
   */
  protected $ctx = null;

  /**
   * Сюда складываются опции после парсинга.
   */
  protected $options = array();

  /**
   * Базовая инициализация, ничего не делает.
   */
  public function __construct(Node $node)
  {
    $this->me = $node;
    $this->user = mcms::user();

    if (null === $this->me->config)
      $this->me->config = array();
  }

  /**
   * Получение имени виджета.
   *
   * @return string внутреннее имя виджета.
   */
  public function getInstanceName()
  {
    return $this->me->name;
  }

  /**
   * Возвращает имя виджета.
   *
   * @return string имя класса, реализующего виджет.
   */
  public function getClassName()
  {
    return $this->me->classname;
  }

  /**
   * Получение информации о виджете.
   *
   * @param string $class имя класса виджета.
   *
   * @return array описание виджета.
   */
  public static function getInfo($class)
  {
    if (!mcms::class_exists($class))
      return array();
    return call_user_func(array($class, 'getWidgetInfo'));
  }

  /**
   * Заглушка, интерфейс требует.
   */
  public function formHookConfigData(array &$data)
  {
  }

  /**
   * Заглушка, интерфейс требует.
   */
  public function formHookConfigSaved()
  {
  }

  /**
   * Получение формы для настройки виджета.
   *
   * Базовая реализация возвращает пустой FieldSet, в который можно добавлять
   * другие компоненты.
   *
   * @return Control описание формы.
   */
  public static function formGetConfig()
  {
    $form = new FieldSetControl(array(
      'name' => 'config',
      'label' => t('Настройка'),
      ));

    return $form;
  }

  /**
   * Проверка доступа.
   *
   * FIXME: deprecated.
   *
   * @return bool true, если пользователь состоит хоть в одной из требуемых
   * групп, иначе false.
   */
  public function checkRequiredGroups()
  {
    if (!empty($this->groups) and is_array($this->groups))
      foreach ($this->groups as $group)
        if ($this->user->hasGroup($group))
          return true;
    return empty($this->groups);
  }

  public function getOptions(Context $ctx)
  {
    return $this->options = $this->getRequestOptions($ctx);
  }

  /**
   * Препроцессор параметров.
   *
   * Используется только в простых случаях, если параметризация виджета целиком
   * основана на запросе пользователя.  Если у виджета есть собственные
   * настройки, которые переопределяют или дополняют переданные извне параметры,
   * нужно переопределить этот метод (можно в начале вызвать родительский).
   *
   * @param Context $ctx контекст запроса.
   *
   * @return array параметры виджета.
   */
  protected function getRequestOptions(Context $ctx)
  {
    if ($this->onlyathome and null !== $ctx->section_id)
      throw new WidgetHaltedException();

    if ($this->onlyiflast and null !== $ctx->document_id)
      throw new WidgetHaltedException();

    $options = array();
    $options['groups'] = array_keys($this->user->getGroups());
    $options['__cleanurls'] = mcms::config('cleanurls');
    $options['#cache'] = true;

    sort($options['groups']);

    $this->ctx = $ctx;

    return $options;
  }

  /**
   * Проверка работоспособности виджета.
   *
   * Реализует работу параметров onlyiflast и onlyifasked, проверку групп.
   * FIXME: deprecated.
   *
   * @return bool true, если виджет может работать.
   */
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

  /**
   * Получение ключа для кэширования виджета.
   *
   * Ключ формируется на основании параметров виджета.
   *
   * @return string ключ (md5-слепок параметров).
   */
  public final function getCacheKey()
  {
    return md5(serialize($this->options));
  }

  /**
   * Формирование страничной листалки.
   *
   * @param integer $total общее количество объектов.
   *
   * @param integer $current номер текущей страницы.
   *
   * @param integer $limit количество объектов на странице.
   *
   * @param integer $default номер первой страницы (по умолчанию: 1,
   * альтернатива: "last").
   *
   * @return array Информация о навигации, ключи: documents (количество), pages
   * (количество), perpage (количество), current (целое число), опциональные:
   * last и next (ссылки на соседние страницы).
   */
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

  /**
   * Обработка POST.
   *
   * FIXME: устранить здесь и во всех виджетах.
   */
  public function onPost(array $options, array $post, array $files)
  {
    if (empty($post))
      throw new WidgetHaltedException();
  }

  /**
   * Диспетчер команд.
   *
   * Используется для упрощения разделения логики на методы.  В соответствии с
   * запрошенной командой вызывает метод onGetКоманда().  Если метод для
   * обработки команды отсутствует, возникает PageNotFoundException().
   *
   * @param array $params Команды.  Например, при ('one', 'two') будет вызван
   * метод onGetOneTwo().
   *
   * @param array &$options параметры для обработчика.
   *
   * @return mixed результат работы обработчика.
   */
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

  /**
   * Шаблонизация виджета.
   *
   * @param DomainNode $page страница, в контексте которой происходит вызов.
   *
   * @param array $args данные для шаблона.
   *
   * @param string $class Имя класса виджета, для обнаружения базового шаблона.
   * Имя шаблона = имя файла, в котором находится класс, расширение заменяется
   * на ".phtml".
   *
   * @return string результат работы шаблона или NULL.
   */
  public final function render(Context $ctx)
  {
    mcms::db()->log('-- widget: '. $this->getInstanceName() .' --');

    if (!is_array($data = $this->onGet($this->options))) {
      $output = $data;
    } else {
      $data['instance'] = $this->getInstanceName();
      $data['lang'] = $ctx->getLang();

      $output = bebop_render_object('widget', $data['instance'], $ctx->theme,
          $data, get_class($this));
    }

    if (false === $output and array_key_exists('html', $data))
      $output = $data['html'];

    if ($ctx->debug() == 'widget')
      if ($this->getInstanceName() == $ctx->get('widget'))
        mcms::debug(array(
          'input' => $data,
          'output' => $output,
          ));

    return $output;
  }

  /**
   * Обращение к конфигурации виджета.
   *
   * @param string $key имя параметра.
   *
   * @return mixed значение параметра или NULL, если такого нет.
   */
  public function __get($key)
  {
    if (isset($this->me->config) and array_key_exists($key, $this->me->config))
      return $this->me->config[$key];
    return null;
  }

  /**
   * Изменение конфигурации виджета.
   *
   * Сохранение конфигурации автоматически НЕ происходит.
   *
   * @param string $key имя параметра.
   *
   * @param mixed $value значение параметра.
   *
   * @return void
   */
  public function __set($key, $value)
  {
    $this->me->config[$key] = $value;
  }

  /**
   * Проверка наличия параметра.
   *
   * @param string $key имя параметра.
   *
   * @return bool true, если параметр есть.
   */
  public function __isset($key)
  {
    return isset($this->me->config) and array_key_exists($key, $this->me->config);
  }

  /**
   * Генератор ошибки 404.
   *
   * Кидает исключение PageNotFoundException().
   * FIXME: устранить.
   *
   * @return void
   */
  protected function emitNotFound($description = null)
  {
    throw new PageNotFoundException(null, $description);
  }

  /**
   * Формирование HTML кода формы.
   *
   * @param string $id идентификатор формы (напр., "my-form").
   *
   * @param array $data Данные для формы.  Если не указаны, вызывается метод
   * formGetData($id).
   *
   * @return Control описание формы или NULL, если формы с таким id нет.
   */
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
      $html = mcms::html('div', array(
        'id' => $id .'-wrapper',
        'class' => $form->wrapper_class,
        ), $html);
    }

    return $html;
  }

  /**
   * Получение формы (заглушка).
   *
   * @return Control всегда возвращает NULL.
   */
  public function formGet($id)
  {
    return null;
  }

  /**
   * Обработка формы.
   *
   * Ничего не делает (заглушка).  При обращении отладчика выводит отладочную
   * информацию для облегчения поиска места, где пропущен обработчик.
   *
   * @param string $id идентификатор формы.
   *
   * @param array $data поступившие от пользователя данные.
   *
   * @return void
   */
  public function formProcess($id, array $data)
  {
    mcms::debug("Unhandled form {$id} in class ". get_class($this) .", data follows.", $data);
  }

  /**
   * Проверка применимости документа к виджету.
   *
   * Если виджет не привязан к документам такого типа — возвращает false.
   * @todo проверить на использование, устранить.
   *
   * @param Node $node обрабатываемый документ.
   *
   * @return bool true, если виджет с такими документами работает.
   */
  protected function checkDocType(Node $node)
  {
    $types = $this->me->linkListParents('type', true);

    $schema = TypeNode::getSchema($node->class);

    if (!empty($types) and !in_array($schema['id'], (array)$types))
      throw new PageNotFoundException();

    return true;
  }
};
