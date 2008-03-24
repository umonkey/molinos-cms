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
      try {
        $data = mcms::db()->getResult("SELECT `data` FROM `node__session` WHERE `sid` = :sid", array(':sid' => $_COOKIE['mcmsid']));

        if (null !== $data)
          return new SessionData(unserialize($data));
      } catch (Exception $e) {
        bebop_debug($e);
      }
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
};
