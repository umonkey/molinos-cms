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

  public static function db($sid, array $data = null)
  {
    try {
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
    catch (TableNotFoundException $e) {
        $t = new TableInfo('node__session');

        if (!$t->exists()) {
          $t->columnSet('sid', array(
            'type' => 'char(32)',
            'required' => true,
            'key' => 'pri',
            ));
          $t->columnSet('created', array(
            'type' => 'datetime',
            'required' => true,
            'key' => 'mul',
            ));
          $t->columnSet('data', array(
            'type' => 'blob',
            'required' => true,
            ));
          $t->commit();

          return self::db($sid, $data);
        }
      }
  }
};
