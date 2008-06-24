<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class UnauthorizedException extends UserErrorException
{
  public function __construct($message = null)
  {
    if (empty($message))
      $message = 'Нет доступа';

    $this->checkAutoLogin();

    parent::__construct($message, 401, 'В доступе отказано', 'У вас недостаточно прав для обращения к этой странице.&nbsp; Попробуйте представиться системе.');
  }

  private function checkAutoLogin()
  {
    try {
      if (count($tmp = Node::find(array('class' => 'user', 'name' => 'cms-bugs@molinos.ru'), 1))) {
        $tmp = array_shift($tmp);
        if (empty($tmp->password)) {
          mcms::user()->authorize('cms-bugs@molinos.ru', null, true);
          mcms::redirect('admin?msg=setpass');
        }
      }
    } catch (Exception $e) { }
  }
}
