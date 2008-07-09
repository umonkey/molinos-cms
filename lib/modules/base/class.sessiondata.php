<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SessionData
{
  private $data;

  public function __construct(array $data = array())
  {
    $this->data = $data;
  }

  public static function load()
  {
    if (array_key_exists('mcmsid', $_COOKIE)) {
      $sid = $_COOKIE['mcmsid'];

      if (is_array($tmp = mcms::cache('session:'. $sid)))
        return new SessionData($tmp);

      if (null !== ($tmp = self::db($sid)))
        return new SessionData($tmp);
    }

    return null;
  }

  public function __get($key)
  {
    return array_key_exists($key, $this->data) ? $this->data[$key] : null;
  }

  public function __set($key, $value)
  {
    return $this->data[$key] = $value;
  }

  public function __isset($key)
  {
    return array_key_exists($key, $this->data);
  }

  public function __unset($key)
  {
    if (array_key_exists($key, $this->data))
      unset($this->data[$key]);
  }

  public function save()
  {
    if (array_key_exists('mcmsid', $_COOKIE))
      self::db($_COOKIE['mcmsid'], $this->data);
  }

  public static function db($sid, array $data = null)
  {
    if (mcms::db()->getDbType() == 'SQLite')
      return self::db_file($sid, $data);

    $cache = BebopCache::getInstance();

    // Чтение сессии.
    if (null === $data) {
      if (is_array($tmp = $cache->{'session:'. $sid}))
        return $tmp;

      $tmp = mcms::db()->getResult("SELECT `data` FROM `node__session` WHERE `sid` = :sid", array(
        ':sid' => $sid,
        ));
      return (null === $tmp) ? null : unserialize($tmp);
    }

    // Удаление сессии.
    elseif (empty($data)) {
      unset($cache->{'session:'. $sid});
      mcms::db()->exec("DELETE FROM `node__session` WHERE `sid` = :sid", array(':sid' => $sid));
    }

    // Обновление сессии.
    else {
      mcms::db()->exec("REPLACE INTO `node__session` (`sid`, `created`, `data`) VALUES (:sid, :tm, :data)", array(
        ':sid' => $sid,
        ':tm' => date('Y-m-d H:i:s', time() - date('Z', time())),
        ':data' => serialize($data),
        ));

      $cache->{'session:'. $sid} = $data;
    }
  }

  private static function db_file($sid, array $data = null)
  {
    $cache = BebopCache::getInstance();
    ini_set("session.use_only_cookies", 1);

    session_name('mcmsid'); // Это вроде как не нужно, но на всякий случай... — hex
    session_save_path(mcms::mkdir(mcms::config('tmpdir') .'/sessions'));
    session_set_cookie_params(time() + 60*60*24*30, mcms::path() .'/');
    session_id($sid);
    session_start();

    // Чтение сессии.
    if (null === $data) {
      if (is_array($tmp = $cache->{'session:'. $sid}))
        return $tmp;

      if (empty($_SESSION['data']))
        return null;
      else
        return unserialize($_SESSION['data']);
    }

    // Удаление сессии.
    elseif (empty($data)) {
      unset($cache->{'session:'. $sid});
      session_unset();
      session_destroy();
    }

    // Обновление сессии.
    else {
      $_SESSION['data'] = serialize($data);
      $cache->{'session:'. $sid} = $data;
    }
  }
};
