<?php

class RatingRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    return mcms::dispatch_rpc(__CLASS__, $ctx);
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

    $db->exec("INSERT INTO `node__rating` (`nid`, `uid`, `ip`, `rate`) VALUES (:nid, :uid, :ip, :rate)", array(
      ':nid' => $options['node'],
      ':uid' => mcms::user()->id,
      ':ip' => $_SERVER['REMOTE_ADDR'],
      ':rate' => $options['rate'],
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
}
