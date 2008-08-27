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
    $this->args = array_merge(array(
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
    if (null === $this->post)
      $this->post = $this->getarg('post', array());

    return array_key_exists($key, $this->post)
      ? $this->post[$key]
      : $default;
  }

  /**
   * Преобразовывает параметр конструктора в удобоваримый.
   * Используется методами get() и post().
   */
  private function getarg($name, $default = null)
  {
    if (array_key_exists($name, $this->args)) {
      $default = $this->args[$name];
      unset($this->args[$name]);
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
      if (array_key_exists('folder', $this->args))
        $this->_folder = $this->args['folder'];

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
    $result = $this->url()->path;

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
}
