<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:
//
// AuthCore: класс, занимающийся авторизацией пользователей.
// Информация о пользователе хранится в $_SESSION['bebop_user'],
// для авторизации используются следующие методы:
//
// userLogIn($name, $pass) -- вход.
// userLogOut() -- выход.

class AuthCore
{
  private static $instance = null;
  private $user;

  private function __construct()
  {
    bebop_session_start();
    if (!empty($_SESSION['user'])) {
      $this->user = User::restore($_SESSION['user']);
    } else {
      $this->user = User::authorize('anonymous', null, true);
    }
    bebop_session_end();
  }

  public static function getInstance()
  {
    if (null === self::$instance)
      self::$instance = new AuthCore();
    return self::$instance;
  }

  public function userLogIn($name, $pass, $bypass = false)
  {
    bebop_session_start();

    $this->user = User::authorize($name, $pass, $bypass);
    $this->user->store();

    bebop_session_end();
  }

  public function userLogOut()
  {
    $this->userLogIn('anonymous', '');
  }

  public function getUser()
  {
    return $this->user;
  }
}
