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
    if (null === $this->_post)
      $this->_post = $this->getarg('post', array());

    return array_key_exists($key, $this->_post)
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
        $default = array_merge($default, $this->_args['files']);
        unset($this->_args['files']);
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
      $result = substr($result, strlen($folder));
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
   * в которой установлена CMS); mcms::redirect() не принимает относительные
   * адреса.
   *
   * @param mixed $url текст ссылки, массив или объект url.
   * @return void
   */
  public function redirect($url, $status = 301)
  {
    $url1 = new url($url);
    $next = strval($url1->getAbsolute($this));

    mcms::redirect($next, $status);
  }

  /**
   * Возвращает метод запроса (GET, POST итд).
   */
  public function method()
  {
    if (array_key_exists('method', $this->_args))
      return $this->_args['method'];
    elseif (array_key_exists('REQUEST_METHOD', $_SERVER))
      return $_SERVER['REQUEST_METHOD'];
    else
      return 'GET';
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
    return '??';
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
        return Node::create('dummy');
      return $this->_args[$key];
    case 'theme':
      if (!array_key_exists($key, $this->_args))
        throw new InvalidArgumentException(t('Свойство %name не определено'
          .' в этом контексте.', array('%name' => $key)));
      return $this->_args[$key];
    }
  }

  private function __set($key, $value)
  {
    switch ($key) {
    case 'section':
    case 'document':
    case 'theme':
    case 'root': // основной раздел
      if (array_key_exists($key, $this->_args))
        throw new InvalidArgumentException(t('Свойство %name уже определено'
          .' в этом контексте.', array('%name' => $key)));
      $this->_args[$key] = $value;
      break;
    default:
      throw new InvalidArgumentException(t('Неизвестное свойство '
        .'контекста: %name.', array('%name' => $key)));
    }
  }

  public function debug()
  {
    if (!bebop_is_debugger())
      return false;

    return $this->get('debug');
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

    $ctx = new Context($this->_args);
    $ctx->_url = new url($url);

    return $ctx;
  }
}
