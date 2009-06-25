<?php

class PollRPC
{
  public static function on_vote(Context $ctx)
  {
    if (!$ctx->get('id'))
      throw new InvalidArgumentException(t('Не указан номер опроса (GET-параметр nid).'));

    $votes = $ctx->post('vote');

    $ctx->db->beginTransaction();
    if (is_array($votes)) {
      foreach ($votes as $i => $vote)
        $ctx->db->exec("INSERT INTO `node__poll` (`nid`, `uid`, `ip`, `option`) VALUES (:nid, :uid, :ip, :option)", array(
          ':nid' => $ctx->get('id'),
          ':uid' => $ctx->user->id,
          ':ip' => $_SERVER['REMOTE_ADDR'],
          ':option' => $vote,
          ));
    } else {
      $ctx->db->exec("INSERT INTO `node__poll` (`nid`, `uid`, `ip`, `option`) VALUES (:nid, :uid, :ip, :option)", array(
        ':nid' => $ctx->get('id'),
        ':uid' => $ctx->user->id,
        ':ip' => $_SERVER['REMOTE_ADDR'],
        ':option' => $votes,
        ));
    }
    $ctx->db->commit();

    return $ctx->getRedirect();
  }

  /**
   * Возвращает результаты по опросу.
   */
  public static function on_get_results(Context $ctx)
  {
    $output = '';

    $id = $ctx->get('id');
    $data = $ctx->db->getResultsKV("option", "count", "SELECT `option`, COUNT(*) AS `count` FROM `node__poll` WHERE `nid` = ? GROUP BY `option`", array($ctx->get('id')));
    foreach ($data as $k => $v)
      $output .= html::em('option', array(
        'count' => $v,
        ), html::cdata($k));

    $voted = $ctx->user->id
      ? $ctx->db->fetch("SELECT COUNT(*) FROM `node__poll` WHERE `nid` = ? AND `uid` = ?", array($id, $ctx->user->id))
      : $ctx->db->fetch("SELECT COUNT(*) FROM `node__poll` WHERE `nid` = ? AND `ip` = ?", array($id, $_SERVER['REMOTE_ADDR']));

    return new Response(html::em('results', array(
      'voted' => (bool)$voted,
      ), $output), 'text/xml');
  }
}
