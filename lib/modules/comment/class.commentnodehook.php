<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentNodeHook
{
  /**
   * @mcms_message ru.molinos.cms.hook.node
   */
  public static function hookNodeUpdate(Context $ctx, Node $node, $op)
  {
    switch ($op) {
    case 'delete':
      $node->onSave("UPDATE `node` SET `deleted` = 1 WHERE `class` = 'comment' AND `id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` = %ID%");
      break;
    case 'erase':
      $node->onSave("DELETE FROM `node` WHERE `class` = 'comment' AND `id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` = %ID%)");
      break;
    case 'create':
      if (!empty($node->doc) and is_numeric($nid = Node::_id($node->doc))) {
        // Собираем прикреплённых пользователей.
        $l1 = $node->getDB()->getResultsV("nid", "SELECT `nid` "
          ."FROM `node__rel` WHERE `tid` = ? AND `nid` IN (SELECT `id` "
          ."FROM `node` WHERE `class` = 'user')", array($nid));

        // Собираем пользователей, комментировавших ранее
        $l2 = $node->getDB()->getResultsV("uid", "SELECT `n`.`uid` "
          ."FROM `node` `n` "
          ."INNER JOIN `node__rel` `r` ON `r`.`nid` = `n`.`id` "
          ."WHERE `r`.`tid` = ? AND `n`.`class` = 'comment'",
            array($nid));

        $uids = array_diff(
          array_unique(array_merge($l1, $l2)),
          array($ctx->user->id)
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
