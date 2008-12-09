<?php

class PollRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    switch ($ctx->get('action')) {
    case 'vote':
      if (!$ctx->get('nid'))
        throw new InvalidArgumentException(t('Не указан номер опроса (GET-параметр nid).'));

      $votes = $ctx->post('vote');

      if (is_array($votes)) {
        foreach ($votes as $i => $vote)
          $ctx->db->exec("INSERT INTO `node__poll` (`nid`, `uid`, `ip`, `option`) VALUES (:nid, :uid, :ip, :option)", array(
            ':nid' => $ctx->get('nid'),
            ':uid' => mcms::user()->id,
            ':ip' => $_SERVER['REMOTE_ADDR'],
            ':option' => $vote,
            ));
      } else {
        $ctx->db->exec("INSERT INTO `node__poll` (`nid`, `uid`, `ip`, `option`) VALUES (:nid, :uid, :ip, :option)", array(
          ':nid' => $ctx->get('nid'),
          ':uid' => mcms::user()->id,
          ':ip' => $_SERVER['REMOTE_ADDR'],
          ':option' => $votes,
          ));
      }
    }

    return $ctx->getRedirect();
  }
}
