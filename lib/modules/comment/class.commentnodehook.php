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
    case 'create':
      if (!empty($node->doc) and is_numeric($nid = Node::_id($node->doc))) {
        // Собираем прикреплённых пользователей.
        $l1 = mcms::db()->getResultsV("nid", "SELECT `nid` "
          ."FROM `node__rel` WHERE `tid` = ? AND `nid` IN (SELECT `id` "
          ."FROM `node` WHERE `class` = 'user')", array($nid));

        // Собираем пользователей, комментировавших ранее
        $l2 = mcms::db()->getResultsV("uid", "SELECT `n`.`uid` "
          ."FROM `node` `n` "
          ."INNER JOIN `node__rel` `r` ON `r`.`nid` = `n`.`id` "
          ."WHERE `r`.`tid` = ? AND `n`.`class` = 'comment'",
            array($nid));

        $uids = array_diff(
          array_unique(array_merge($l1, $l2)),
          array(Context::last()->user->id)
          );

        $body = mcms::render(__CLASS__, array(
          'mode' => 'new',
          'comment' => $node->getRaw(),
          ));

        if (!empty($body) and !empty($uids))
          foreach ($uids as $uid) {
            if (empty($node->doc))
              $subject = t('Новый комментарий');
            else
              $subject = t('Новый комментарий [%id]',
                array('%id' => Node::_id($node->doc)));
            mcms::mail(null, $uid, $subject, $body);
          }
      }
    }
  }
}
