<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

// {get_current_user assign=user}
function smarty_function_get_current_user($params, &$smarty)
{
  static $results = null;
  static $resultx = null;

  if (empty($params['extended']))
    $result = &$results;
  else
    $result = &$resultx;

  if ($result === null) {
    $user = mcms::user();

    if (empty($params['extended']) or !$user->id) {
      $result = array(
        'uid' => $user->id,
        'name' => $user->login,
        'title' => $user->name,
        'groups' => $user->getGroups(),
        );
    } else {
      // Основная информация о пользователе.
      $node = Node::load($user->id);
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
