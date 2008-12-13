<?php

class RatingCleaner implements iNodeHook
{
  public static function hookNodeUpdate(Node $node, $op)
  {
    if ($op == 'erase') {
      if ('user' == $node->class)
        mcms::db()->exec("DELETE FROM `node_rating` WHERE `nid` = :nid OR `uid` = :uid", array(':nid' => $node->id, ':uid' => $node->id));
      else
        mcms::db()->exec("DELETE FROM `node_rating` WHERE `nid` = :nid", array(':nid' => $node->id));
    }
  }
}
