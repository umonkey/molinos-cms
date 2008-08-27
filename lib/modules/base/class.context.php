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
   * Хранит параметры в том виде, в котором они получены при создании объекта.
   * При обращении парсятся и копируются в $url, $post и $files.
   */
  private $_args;

  private function __construct(array $args = array())
  {
    $this->args = $args;
  }

  /**
   * Создание простого контекста.
   *
   * @param $args ключ url содержит текущий URL, post и files — сырые данные.
   *
   * @return Context $ctx описание контекста.
   */
  public static function make(array $args = array())
  {
    return new Context(array_merge(array(
      'url' => null,
      'post' => $_POST,
      'files' => $_FILES,
      ), $args));
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
   * Доступ к пути текущего запроса.
   */
  public function path()
  {
    return $this->url()->path;
  }

  /**
   * Доступ к текущему URL.
   */
  public function url()
  {
    if (null === $this->_url)
      $this->_url = new url($this->getarg('url'), /* readonly = */ true);
    return $this->_url;
  }
}
