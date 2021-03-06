<?php

class RatingRPC
{
  public static function on_rpc(Context $ctx)
  {
    return parent::hookRemoteCall($ctx, __CLASS__);
  }

  /**
   * Кастинг голоса.
   */
  public static function rpc_rate(Context $ctx)
  {
    $node = $ctx->post('node');
    $rate = $ctx->post('rate');

    if (empty($node) or empty($rate))
      throw new RuntimeException(t('Синтаксис: &node=id&rate=value'));

    $ctx->db->exec("INSERT INTO `node__rating` (`nid`, `uid`, `ip`, `rate`) VALUES (:nid, :uid, :ip, :rate)", array(
      ':nid' => $node,
      ':uid' => $ctx->user->id,
      ':ip' => $_SERVER['REMOTE_ADDR'],
      ':rate' => $rate,
      ));

    return new Response(t('Голос принят.'));
  }

  /**
   * Получение информации о голосах.
   */
  public static function rpc_getrates(Context $ctx)
  {
    $nodes = explode(',', $ctx->get('node'));

    foreach ($nodes as $nid) {
      if (!is_numeric($nid) or empty($nid)) {
        $nodes = null;
        break;
      }
    }

    if (empty($nodes))
      throw new BadRequestException(t('Синтаксис: &node=id[,id,...]'));

    $data = $ctx->db->getResults('SELECT `nid`, AVG(`rate`) AS `avg`, SUM(`rate`) AS `sum`, COUNT(`rate`) AS `count` FROM `node__rating` WHERE `nid` IN (' . join(', ', $nodes) . ') GROUP BY `nid`');

    bebop_on_json(array(
      'rates' => $data,
      'len' => count($data),
      ));

    throw new BadRequestException(t('Этот вызов следует отправлять только через XMLHttpRequest в режиме JSON.'));
  }

  /**
   * Проверяет, голосовал ли текущий пользователь за документ.
   */
  protected function checkUserVoted(Context $ctx, $nid)
  {
    $user = $ctx->user;

    if ($user->id == 0)
      $status = $ctx->db->fetch("SELECT COUNT(*) FROM `node__rating` WHERE `nid` = :nid AND `uid` = 0 AND `ip` = :ip", array(':nid' => $this->ctx->document->id, ':ip' => $_SERVER['REMOTE_ADDR']));
    else
      $status = $ctx->db->fetch("SELECT COUNT(*) FROM `node__rating` WHERE `nid` = :nid AND `uid` = :uid", array(':nid' => $this->ctx->document->id, ':uid' => $user->id));

    return !empty($status);
  }
}
