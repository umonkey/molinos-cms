<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentNodeHook implements iNodeHook
{
  public static function hookNodeUpdate(Node $node, $op)
  {
    switch ($op) {
    case 'delete':
      mcms::db()->exec("UPDATE `node` SET `deleted` = 1 WHERE `class` = 'comment' AND `id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` = :tid)", array(':tid' => $node->id));
      break;
    case 'erase':
      mcms::db()->exec("DELETE FROM `node` WHERE `class` = 'comment' AND `id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` = :tid)", array(':tid' => $node->id));
      break;
    }
  }
}
