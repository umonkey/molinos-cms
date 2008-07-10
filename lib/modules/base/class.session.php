<?php

class Session
{
  const cookie = 'mcmsid1';

  private $id = null;
  private $data = null;
  private $_hash = null;

  private static $instance = null;

  public static function instance()
  {
    if (null === self::$instance)
      self::$instance = new Session();
    return self::$instance;
  }

  protected function load()
  {
    $this->data = array();

    if (!empty($_COOKIE[self::cookie])) {
      $this->id = $_COOKIE[self::cookie];

      $tmp = mcms::db()->getResult("SELECT `data` FROM node__session "
        ."WHERE `sid` = ?", array($this->id));

      if (!empty($tmp) and is_array($arr = unserialize($tmp)))
        $this->data = $arr;
    }

    $this->_hash = $this->hash();
  }

  private function hash()
  {
    return md5(serialize($this->data));
  }

  public function save()
  {
    if ($this->hash() != $this->_hash) {
      if (null === $this->id)
        $this->id = md5($_SERVER['REMOTE_ADDR'] . microtime(false) . rand());

      mcms::db()->exec("DELETE FROM node__session WHERE `sid` = ?",
        array($this->id));

      if (!empty($this->data))
        mcms::db()->exec("INSERT INTO node__session (`created`, `sid`, `data`) "
          ."VALUES (UTC_TIMESTAMP(), ?, ?)", array($this->id, serialize($this->data)));

      static $sent = false;

      if (!$sent) {
        $sent = true;

        $path = mcms::path() .'/';
        $time = time() + 60*60*24*30;
        $name = self::cookie;

        setcookie($name, empty($this->data) ? null : $this->id, $time, $path);

        mcms::log('session', "cookie set: {$name}, {$this->id}, {$time}, {$path}");
      }
    }
  }

  public function __get($key)
  {
    if (null === $this->data)
      $this->load();

    return array_key_exists($key, $this->data)
      ? $this->data[$key]
      : null;
  }

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

  public function raw()
  {
    return $this->data;
  }
}
