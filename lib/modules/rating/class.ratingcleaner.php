<?php

class RatingCleaner
{
  /**
   * @mcms_message ru.molinos.cms.hook.node
   */
  public static function hookNodeUpdate(Context $ctx, Node $node, $op)
  {
    if ($op == 'erase') {
      $db = $node->getDB();
      if ('user' == $node->class)
        $db->exec("DELETE FROM `node_rating` WHERE `nid` = :nid OR `uid` = :uid", array(':nid' => $node->id, ':uid' => $node->id));
      else
        $db->exec("DELETE FROM `node_rating` WHERE `nid` = :nid", array(':nid' => $node->id));
    }
  }
}
