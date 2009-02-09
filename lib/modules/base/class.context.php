<?php
/**
 * Средства для доступа к контексту.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Интерфейс для доступа к контексту.
 *
 * Позволяет обращаться к текущему URL, GET/POST параметрам, файлам.
 * Модификация не допускается — только на этапе конструкции.
 *
 * @package mod_base
 * @subpackage Core
 */
class Context
{
  private $_url;
  private $_post;
  private $_files;
  private $_profile = array();
  private $user = null;

  /**
   * Путь к инсталляции CMS относительно корня сайта.
   *
   * Например, если CMS доступна как /mcms/index.php, здесь будет "/mcms".
   */
  private $_folder;

  /**
   * Хранит параметры в том виде, в котором они получены при создании объекта.
   * При обращении парсятся и копируются в $url, $post и $files.
   */
  private $_args;

  /**
   * Файлы, удаляемые в конце работы.
   */
  private static $_killfiles = array();

  private static $_last = null;

  /**
   * Может ли текущий пользователь видеть отладочную информацию?
   */
  private $_debug;

  /**
   * Интерфейс к БД.
   */
  private $_db = null;

  /**
   * Создание простого контекста.
   *
   * @param $args ключ url содержит текущий URL, post и files — сырые данные.
   *
   * @return Context $ctx описание контекста.
   */
  public function __construct(array $args = array())
  {
    $this->_args = array_merge(array(
      'url' => null,
      'post' => $_POST,
      'files' => $_FILES,
      ), $args);

    if (null === ($tmp = mcms::config('debuggers')))
      $this->_debug = true;
    else
      $this->_debug = mcms::matchip($_SERVER['REMOTE_ADDR'], $tmp);

    if (null === self::$_last)
      self::$_last = $this;
  }

  public static function last()
  {
    if (null === self::$_last)
      throw new RuntimeException(t('Контекст ещё не инициализирован.'));
    return self::$_last;
  }

  /**
   * Доступ к GET-параметрам.
   *
   * @param string $key имя параметра.
   * @param string $default значение, возвращаемое при отсутствии параметра.
   * @return string значение параметра.
   */
  public function get($key, $default = null)
  {
    if (null === $this->_url)
      $this->_url = new url($this->getarg('url'), /* readonly = */ true);

    return $this->_url->arg($key, $default);
  }

  /**
   * Доступ к POST-параметрам.
   *
   * @param string $key имя параметра.
   * @param string $default значение, возвращаемое при отсутствии параметра.
   * @return string значение параметра.
   */
  public function post($key, $default = null)
  {
    if (!$this->method('post'))
      throw new InvalidArgumentException(t('Обращение к данным '
        .'формы возможно только при запросах типа POST.'));

    if (null === $this->_post)
      $this->_post = $this->getarg('post', array());

    return !empty($this->_post[$key])
      ? $this->_post[$key]
      : $default;
  }

  /**
   * Преобразовывает параметр конструктора в удобоваримый.
   * Используется методами get() и post().
   */
  private function getarg($name, $default = null)
  {
    if (array_key_exists($name, $this->_args)) {
      $default = $this->_args[$name];

      if ('post' == $name and !empty($this->_args['files'])) {
        foreach ($this->_args['files'] as $k => $v) {
          if (array_key_exists($k, $this->_args['post']))
            $v = array_merge($v, $this->_args['post'][$k]);

          $this->_args['post'][$k] = $v;
        }

        unset($this->_args['files']);

        $default = array_merge($default, $this->_args[$name]);
      }

      unset($this->_args[$name]);
    }

    return $default;
  }

  /**
   * Определение папки, в которой установлена CMS.
   *
   * @return string папка, в которой установлена CMS.  Например, если она
   * доступна как "/mcms/index.php", значение будет "/mcms"; если в корень
   * сайта, или запуск происходит из консоли — пустая строка.
   */
  public function folder()
  {
    if (null === $this->_folder) {
      // Папка указана при создании объекта (используется в основном в тестах).
      if (array_key_exists('folder', $this->_args))
        $this->_folder = $this->_args['folder'];

      // Запуск через веб, всё просто.
      elseif (!empty($_SERVER['HTTP_HOST']))
        $this->_folder = trim(dirname($_SERVER['SCRIPT_NAME']), '/');

      // Запуск из командной строки.
      else
        $this->_folder = '';
    }

    return empty($this->_folder)
      ? ''
      : '/'. trim($this->_folder, '/');
  }

  /**
   * Определение имени запрошенной страницы.
   *
   * @return string имя текущей страницы, без параметров (часть URL от имени
   * хоста до вопросительного знака).  Имя не содержит путь к CMS; например,
   * если система расположена в папке "mcms" и запрошен урл "/mcms/node/123",
   * метод вернёт "node/123".
   */
  public function query()
  {
    $result = $this->get('q', $this->url()->path);

    // Отсекание папки из абсолютных урлов.
    if ('/' == substr($result, 0, 1)) {
      $folder = $this->folder() .'/';
      $result = strval(substr($result, strlen($folder)));
    }

    return $result;
  }

  /**
   * Доступ к текущему URL.
   *
   * @return url описание ссылки.
   */
  public function url()
  {
    if (null === $this->_url)
      $this->_url = new url($this->getarg('url'), /* readonly = */ true);
    return $this->_url;
  }

  /**
   * Перенаправление в контексте CMS.
   *
   * Позволяет перенаправлять используя относительные урлы (относительно папки,
   * в которой установлена CMS).
   *
   * @param mixed $url текст ссылки, массив или объект url.
   * @return void
   */
  public function redirect($url, $status = 301, Node $node = null)
  {
    $url1 = new url($url);
    $next = $url1->getAbsolute($this);

    if (null !== $node and $node->id) {
      if (!$node->published)
        $mode = 'pending';
      elseif ($node->isNew())
        $mode = 'created';
      else
        $mode = 'updated';

      $url = new url($next);

      if ('%ID' == $url->arg('id')) {
        $url->setarg('id', $node->id);
      } else {
        $url->setarg($mode, $node->id);
        $url->setarg('type', $node->class);
      }

      $next = $url->string();
    }

    $r = new Redirect($next, $status);
    $r->send();
  }

  /**
   * Возвращает метод запроса (GET, POST итд).
   */
  public function method($check = null)
  {
    if (array_key_exists('method', $this->_args))
      $value = $this->_args['method'];
    elseif (array_key_exists('REQUEST_METHOD', $_SERVER))
      $value = $_SERVER['REQUEST_METHOD'];
    else
      $value = 'GET';

    if (null === $check)
      return $value;
    else
      return !strcasecmp($value, $check);
  }

  public function checkMethod($method)
  {
    if (!$this->method($method))
      throw new RuntimeException(t('Такие запросы следует отправлять методом %method.', array(
        '%method' => $method,
        )));
  }

  /**
   * Возвращает имя текущего хоста.
   */
  public function host()
  {
    if (array_key_exists('host', $this->_args))
      return $this->_args['host'];
    elseif ($host = $this->url()->host)
      return $host;
    elseif (array_key_exists('HTTP_HOST', $_SERVER))
      return $_SERVER['HTTP_HOST'];
    else
      return 'localhost';
  }

  /**
   * Возвращает язык, предпочитаемый пользователем.
   *
   * Язык выбирается из списка доступных для текущей страницы.  Если язык
   * неопределён — возвращает '??'.
   */
  public function getLang()
  {
    return 'ru';
  }

  private function __get($key)
  {
    switch ($key) {
    case 'post':
      if (null === $this->_post)
        $this->_post = $this->getarg('post', array());
      if (null === $this->_post)
        return array();
      else
        return $this->_post;
    case 'section':
    case 'document':
    case 'root':
      if (!array_key_exists($key, $this->_args))
        return null;
      return $this->_args[$key];

    case 'theme':
    case 'moderatoremail':
      if (!array_key_exists($key, $this->_args))
        throw new InvalidArgumentException(t('Свойство %name не определено'
          .' в этом контексте.', array('%name' => $key)));
      return $this->_args[$key];

    case 'db':
      if (null === $this->_db)
        // Тип исключения не менять, многие на него ориентируются.
        throw new NotConnectedException();
      elseif (!class_exists('PDO_Singleton'))
        // Нужно только для подавления ошибки в автозагрузке классов,
        // которая иногда почему-то не срабатывает.
        throw new RuntimeException(t('Отсутствует общий драйвер доступа к БД.'));
      elseif (is_string($this->_db))
        $this->_db = PDO_Singleton::connect($this->_db);
      return $this->_db;

    // Возвращает профиль пользователя.
    case 'user':
      if (null === $this->user)
        $this->user = User::identify($this);
      return $this->user;
    }
  }

  private function __set($key, $value)
  {
    switch ($key) {
    case 'section':
    case 'document':
    case 'root': // основной раздел
      if (array_key_exists($key, $this->_args))
        throw new InvalidArgumentException(t('Свойство %name уже определено'
          .' в этом контексте.', array('%name' => $key)));
      $this->_args[$key] = NodeStub::create($value, $this->db);
      break;

    case 'moderatoremail':
    case 'method':
    case 'theme':
      if (array_key_exists($key, $this->_args))
        throw new InvalidArgumentException(t('Свойство %name уже определено'
          .' в этом контексте.', array('%name' => $key)));
      $this->_args[$key] = $value;
      break;

    case 'db':
      if (!is_string($value))
        throw new InvalidArgumentException(t('Параметры подключения должны быть строкой.'));
      $this->_db = $value;
      break;

    default:
      throw new InvalidArgumentException(t('Неизвестное свойство '
        .'контекста: %name.', array('%name' => $key)));
    }
  }

  private function __isset($key)
  {
    switch ($key) {
    case 'db':
      return $this->_db !== null;
    default:
      return array_key_exists($key, $this->_args);
    }
  }

  public function debug($type = null)
  {
    if (!$this->canDebug())
      return false;

    $result = $this->get('debug');

    return (null === $type)
      ? $result
      : !strcasecmp($result, $type);
  }

  /**
   * Возвращает контекст для виджета.
   */
  public function forWidget($name)
  {
    // Формируем новый урл, с параметрами виджета (и только).
    $url = $this->url()->as_array();

    if (!array_key_exists($name, $url['args']))
      $url['args'] = array();
    else
      $url['args'] = $url['args'][$name];

    if ('widget' == ($url['args']['debug'] = $this->debug()))
      $url['args']['widget'] = $this->get('widget');

    $url['args']['q'] = $this->query();
    $url['args']['destination'] = $this->get('destination');

    $ctx = new Context($this->_args);
    $ctx->_db = $this->_db;
    $ctx->_url = new url($url);

    return $ctx;
  }

  public static function killFile($path)
  {
    self::$_killfiles[] = $path;
  }

  public function getRedirect($default = '')
  {
    if (null !== ($next = $this->get('destination')))
      ;
    elseif ($this->method('post') and null !== ($next = $this->post('destination')))
      ;
    else
      $next = $default;

    if (null === $next)
      throw new RuntimeException(t('Не удалось получить адрес перенаправления: он должен быть указан в параметре destination.'));

    $url = new url($next);

    return new Redirect($url->getAbsolute($this));
  }

  public function canDebug()
  {
    return $this->_debug;
  }

  /**
   * Добавление дополнительного элемента.
   */
  public function addExtra($type, $id)
  {
    if (!array_key_exists('extras', $this->_args))
      $this->_args['extras'] = array();

    // Поиск дублей.
    foreach ($this->_args['extras'] as $item)
      if ($item[0] == $type and $item[1] == $id)
        return;

    $this->_args['extras'][] = array($type, $id);
  }

  /**
   * Получение списка дополнительных элементов.
   */
  public function getExtras()
  {
    if (!array_key_exists('extras', $this->_args))
      return array();

    return $this->_args['extras'];
  }

  public function getExtrasXML()
  {
    $output = '';

    foreach ($this->getExtras() as $e)
      $output .= html::em('item', array(
        'type' => $e[0],
        'value' => $e[1],
        ));

    return empty($output)
      ? ''
      : html::em('extras', $output);
  }
}
