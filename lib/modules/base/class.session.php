<?php
/**
 * Работа с сессиями.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Работа с сессиями.
 *
 * Сессионные данные хранятся в таблице node__session, в полях: sid, created,
 * data.
 *
 * @package mod_base
 * @subpackage Core
 */
class Session
{
  /**
   * Имя cookie.
   */
  const cookie = 'mcmsid1';

  private $id = null;
  private $data = null;
  private $_hash = null;

  private static $instance = null;

  /**
   * Возвращает объект для взаимодействия с сессией.
   */
  public static function instance()
  {
    if (null === self::$instance)
      self::$instance = new Session();
    return self::$instance;
  }

  /**
   * Загружает сессионные данные из БД.
   *
   * Загрузка выполняется только если есть cookie с нужным именем.
   *
   * @return Session $this
   */
  protected function load()
  {
    $this->data = array();

    if ($this->id = $this->getSessionId()) {
      if (false === ($tmp = mcms::cache($cid = 'session:'. $this->id))) {
        $tmp = mcms::db()->getResult("SELECT `data` FROM node__session "
          ."WHERE `sid` = ?", array($this->id));
        mcms::cache($cid, $tmp);
      }

      if (!empty($tmp)) {
        if (is_string($tmp))
          $tmp = unserialize($tmp);
        if (is_array($tmp))
          $this->data = $tmp;
      }
    }

    $this->_hash = $this->hash();

    return $this;
  }

  private function hash()
  {
    return md5(serialize($this->data));
  }

  /**
   * Сохранение сессионных данных.
   *
   * Перед сохранением из БД удаляются ранее существовавшие данные этой сессии.
   * @todo менять sid надо при _каждом_ сохранении, см. Session Fixation
   * Vulnerability.
   *
   * Если сессия не была загружена — возникает RuntimeException().
   *
   * @see http://en.wikipedia.org/wiki/Session_fixation
   *
   * @return Session $this
   */
  public function save($force = false)
  {
    static $sent = false;

    if ($force)
      $sent = false;

    // При запуске из консоли сессии никуда не сохраняем.
    if (empty($_SERVER['REMOTE_ADDR']))
      return;

    if (null === $this->data)
      throw new RuntimExeption(t('Session is being saved '
        .'without having been loaded.'));

    if ($this->hash() != $this->_hash) {
      if (null === $this->id)
        $this->id = $this->getsessionId();

      mcms::db()->exec("DELETE FROM node__session WHERE `sid` = ?",
        array($this->id));

      if (!empty($this->data)) {
        mcms::db()->exec("INSERT INTO node__session (`created`, `sid`, `data`) "
          ."VALUES (UTC_TIMESTAMP(), ?, ?)",
          array($this->id, serialize($this->data)));
        mcms::cache('session:'. $this->id, serialize($this->data));
      } else {
        mcms::cache('session:'. $this->id, false);
      }

      if (!$sent) {
        $sent = true;

        $path = mcms::path() .'/';
        $time = time() + 60*60*24*30;
        $name = self::cookie;

        if (!headers_sent()) {
          setcookie($name, empty($this->data) ? null : $this->id, $time, $path);
          mcms::log('session', "cookie set: {$name}={$this->id}");
        }
      }
    }

    return $this;
  }

  /**
   * Получение идентификатора сессии.
   *
   * @return string Идентификатор текущей сессии.  Если сессия не запущена —
   * NULL.
   */
  public function id()
  {
    return $this->id;
  }

  /**
   * Обращение к сессионной переменной.
   *
   * @param string $key Имя переменной.
   *
   * @return mixed Значение переменной.  NULL, если такой переменной нет.  Если
   * сессия ещё не загружена, происходит прозрачная загрузка.
   */
  public function __get($key)
  {
    if (null === $this->data)
      $this->load();

    if ('id' == $key)
      return $this->id;

    return array_key_exists($key, $this->data)
      ? $this->data[$key]
      : null;
  }

  /**
   * Изменение сессионной переменной.
   *
   * @param string $key имя переменной.
   *
   * @param mixed $value новое значение.
   *
   * @return void
   */
  public function __set($key, $value)
  {
    if (null === $this->data)
      $this->load();

    if (null !== $value)
      $this->data[$key] = $value;
    elseif (array_key_exists($key, $this->data))
      unset($this->data[$key]);

    $this->save();
  }

  /**
   * Получение слепка сессии.
   *
   * @return array Все сессионные данные.
   */
  public function raw()
  {
    return $this->data;
  }

  public function reset($data = array())
  {
    $this->data = $data;
  }

  private function getSessionId()
  {
    if (!empty($_COOKIE[self::cookie]))
      return $_COOKIE[self::cookie];

    if (!empty($_GET['sid']) and $_GET['q'] == 'openid.rpc')
      return $_GET['sid'];

    $id = md5($_SERVER['REMOTE_ADDR'] . microtime(false) . rand());

    // mcms::flog('session', $id .': new id for '. $_SERVER['REMOTE_ADDR']);

    return $id;
  }
}
