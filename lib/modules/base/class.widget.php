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
   * Имя виджета (инстанса).
   */
  protected $name;

  /**
   * Параметры виджета.
   */
  private $config;

  /**
   * Контекст для рендеринга.
   */
  protected $ctx;

  /**
   * Базовая инициализация, ничего не делает.
   */
  public function __construct($name, array $data)
  {
    $this->name = $name;
    $this->config = array_key_exists('config', $data)
      ? $data['config']
      : array();
  }

  /**
   * Получение имени виджета.
   *
   * @return string внутреннее имя виджета.
   */
  public function getInstanceName()
  {
    return $this->name;
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
    if (!class_exists($class))
      return array();
    return call_user_func(array($class, 'getWidgetInfo'));
  }

  /**
   * Получение формы для настройки виджета.
   *
   * @return Control описание формы.
   */
  public static function getConfigOptions()
  {
    return array();
  }

  public function setContext(Context $ctx)
  {
    return $this->options = $this->getRequestOptions($this->ctx = $ctx);
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
    if (null != $this->groups and !$this->checkGroups())
      throw new WidgetHaltedException();

    if ($this->onlyathome and $ctx->section->id)
      throw new WidgetHaltedException();

    if ($this->onlyiflast and null !== $ctx->document->id)
      throw new WidgetHaltedException();

    $options = array();
    $options['__cleanurls'] = !empty($_GET['__cleanurls']);
    $options['#cache'] = true;
    $options['#instance'] = $this->getInstanceName();
    $options['theme'] = $ctx->theme;

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
    if (empty($this->options['#cache']))
      return null;
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
    return mcms::pager($total, $current, $limit, $this->name . '.page', $default);
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

    if (empty($params[0]))
      throw new RuntimeException(t('Метод dispatch для виджета %name (%class) вызван без параметра.', array(
        '%name' => $this->name,
        '%class' => get_class($this),
        )));

    array_unshift($params, 'GET' /* $_SERVER['REQUEST_METHOD'] */);

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
   * @return string результат работы шаблона или NULL.
   */
  public final function render(Context $ctx)
  {
    try {
      $this->ctx = $ctx->forWidget($this->name);

      if (!is_array($options = $this->getRequestOptions($this->ctx))) {
        $this->debug(array(), array(), $options);
        return "<!-- widget {$this->name} halted. -->";
      }

      if (array_key_exists('#cache', $options) and empty($options['#cache']))
        $ckey = null;
      elseif ($ctx->get('nocache') and $ctx->canDebug())
        $ckey = null;
      else
        $ckey = 'widget:' . $this->name . ':' . md5(serialize($options));

      if (null !== $ckey and is_array($cached = mcms::cache($ckey))) {
        $this->debug($options, array(), $cached['content']);
        return $cached['content'];
      }

      if (null === ($data = $this->onGet($options)))
        $result = "<!-- widget {$this->name} had nothing to say. -->";
      elseif (!is_string($data)) {
        throw new RuntimeException(t('Виджет %name (%class) вернул мусор вместо XML.', array(
          '%name' => $this->name,
          '%class' => get_class($this),
          )));
      } else {
        // TODO: добавить заголовок виджета
        $result = html::em('widget', array(
          'name' => $this->name,
          'class' => get_class($this),
          ), $data);
      }

      if (null !== $ckey) {
        mcms::cache($ckey, array(
          'content' => $result,
          ));
      }
    } catch (WidgetHaltedException $e) {
      return false;
    }

    return $result;
  }

  private function debug(array $options, array $data, $result)
  {
    if (!$this->ctx->debug('widget') or $this->name != $this->ctx->get('widget'))
      return;

    $dump = array(
      'instance' => $this->name,
      'class_name' => get_class($this),
      'class_path' => Loader::getClassPath(get_class($this)),
      'config' => $this->config,
      'options' => $options,
      'input' => $data,
      'output' => $result,
      );

    mcms::debug($dump);
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
    return array_key_exists($key, $this->config)
      ? $this->config[$key]
      : null;
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
    return array_key_exists($key, $this->config);
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
  protected function formRender($id, $data = null)
  {
    if (null === ($form = $this->formGet($id)))
      return null;

    if (null === $data)
      $data = $this->formGetData($id);

    if (is_array($data))
      $data = Control::data($data);

    $form->id = $id;

    if (!($form instanceof Form))
      throw new InvalidArgumentException(t("Значение, полученное от метода formGet(%id) виджета %class не является формой.", array('%id' => $id, '%class' => get_class($this))));

    if (null === ($form->findControl('destination')))
      $form->addControl(new HiddenControl(array(
        'value' => 'destination',
        )));

    $html = $form->getXML($data);

    if (!empty($html) and null !== $id) {
      $html = html::em('div', array(
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

    $schema = $this->getSchema();

    if (!empty($types) and !in_array($schema['id'], (array)$types))
      throw new PageNotFoundException();

    return true;
  }

  protected function checkGroups()
  {
    $diff = array_intersect(
      (array)$this->groups,
      array_keys(mcms::user()->getGroups()));

    return !empty($diff);
  }

  /**
   * Возвращает конкретный виджет.
   */
  public static function get($name)
  {
    $s = new Structure();

    if (!count($list = $s->findWidgets(array($name))))
      throw new InvalidArgumentException(t('Виджета %name не существует.', array(
        '%name' => $name,
        )));

    $name = array_shift(array_keys($list));
    $info = array_shift($list);

    if (!class_exists($info['class']))
      throw new RuntimeException(t('Виджет %name использует неизвестный класс %class.', array(
        '%name' => $name,
        '%class' => $info['class'],
        )));

    return new $info['class']($name, $info);
  }
};
