<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

function smarty_function_get_current_user($params, &$smarty)
{
  static $results = null;
  static $resultx = null;

  if (empty($params['extended']))
    $result = &$results;
  else
    $result = &$resultx;

  if ($result === null) {
    $user = AuthCore::getInstance()->getUser();

    if (empty($params['extended'])) {
      $result = array(
        'uid' => $user->getUid(),
        'name' => $user->getName(),
        'title' => $user->getTitle(),
        'groups' => $user->getGroups(),
        );
    } else {
      // Основная информация о пользователе.
      $node = Node::load($user->getUid());
      $result = $node->getRaw();
      unset($result['password']);

      // Информация о группах.
      foreach (Node::find(array('class' => 'group', 'id' => array_keys($user->getGroups()))) as $group)
        $result['groups'][] = $group->getRaw();
    }
  }

  if (!empty($params['assign']))
    $smarty->assign($params['assign'], $result);
  else
    return $result['name'];
}
