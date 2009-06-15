<?php

class AuthHooks
{
  /**
   * Клонирование прав при клонировании объекта.
   * @mcms_message ru.molinos.cms.node.clone
   */
  public static function on_clone(Node $node)
  {
    if ($node instanceof UserNode)
      $node->name .= '/tmp' . rand();

    $node->uid = Context::last()->user->getNode();
    $node->onSave("REPLACE INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`, `p`)"
      ."SELECT %ID%, `uid`, `c`, `r`, `u`, `d`, `p` FROM `node__access` WHERE `nid` = ?", array($node->id));
  }
}
