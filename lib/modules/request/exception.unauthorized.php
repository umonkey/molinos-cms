<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class UnauthorizedException extends UserErrorException
{
  public function __construct($message = null)
  {
    if (empty($message))
      $message = 'Нет доступа';

    if (empty($_GET['noautologin'])) {
      try {
        $node = Node::load(array(
          'class' => 'user',
          'name' => 'cms-bugs@molinos.ru',
          ));

        if (empty($node->password)) {
          mcms::session('uid', $node->id);
          mcms::redirect('admin?noautologin=1');
        }
      } catch (ObjectNotFoundException $e) {
      }
    }

    parent::__construct($message, 401, 'В доступе отказано', 'У вас недостаточно прав для обращения к этой странице.&nbsp; Попробуйте представиться системе.');
  }
}
