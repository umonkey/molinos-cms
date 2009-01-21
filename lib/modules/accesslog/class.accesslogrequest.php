<?php

class AccessLogRequest implements iRequestHook
{
  public static function hookRequest(Context $ctx = null)
  {
    if (null !== $ctx) {
      $conf = mcms::modconf('accesslog');

      try {
        if (!empty($conf['options']) and is_array($conf['options'])) {
          if (in_array('section', $conf['options']) and isset($ctx->section->id))
            self::logNode($ctx->section->id);

          if (in_array('document', $conf['options']) and isset($ctx->document->id))
            self::logNode($ctx->document->id);
        }
      } catch (PDOException $e) {
        // Обычно здесь обламываемя при обращении к несуществующему урлу.
      }
    }
  }

  public static function logNode($nid)
  {
    $args = array(
      ':ip' => empty($_SERVER['REMOTE_ADDR']) ? null : $_SERVER['REMOTE_ADDR'],
      ':referer' => empty($_SERVER['HTTP_REFERER']) ? null : $_SERVER['HTTP_REFERER'],
      ':nid' => $nid,
      );

    mcms::db()->exec("INSERT INTO `node__astat` (`nid`, `timestamp`, `ip`, `referer`) VALUES (:nid, UTC_TIMESTAMP(), :ip, :referer)", $args);
  }
}
