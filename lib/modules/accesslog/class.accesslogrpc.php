<?php

class AccessLogRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    mcms::dispatch_rpc(__CLASS__, $ctx);
  }

  public static function rpc_stat(Context $ctx)
  {
    if (null === ($node = $ctx->get('node')))
      throw new RuntimeException(t('Usage: accesslog.rpc?action=stat&node=id'));

    $data = mcms::db()->getResults('SELECT * FROM `node__astat` WHERE `nid` = ?',
      array($node));

    $id = 1;
    $result = '<table border="1" cellspacing="0" cellpadding="4"><thead><tr><th align="right">#</th><th>ip</th><th>from</th></tr></thead><tbody>';

    foreach ($data as $row)
      $result .= '<tr><td align="right">'. ($id++) .'</td><td>'. $row['ip'] .'</td><td>'. htmlspecialchars($row['referer']) .'</td></tr>';

    $result .= '</tbody></table>';

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Length: '. strlen($result));
    die($result);
  }
}
