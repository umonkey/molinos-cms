<?php

class AccessLogRequest implements iRequestHook
{
  public static function hookRequest(Context $ctx = null)
  {
    if (null !== $ctx) {
      // Лог в SQLite приводит к тому, что каждый посетитель
      // блокирует БД эксклюзивно.
      if ('SQLite' == $ctx->db->getDbType())
        return;

      $conf = mcms::modconf('accesslog');

      try {
        if (!empty($conf['options']) and is_array($conf['options'])) {
          if (in_array('section', $conf['options']) and isset($ctx->section->id))
            self::logNode($ctx, $ctx->section->id);

          if (in_array('document', $conf['options']) and isset($ctx->document->id))
            self::logNode($ctx, $ctx->document->id);
        }
      } catch (PDOException $e) {
        // Обычно здесь обламываемя при обращении к несуществующему урлу.
      }
    }
  }

  private static function logNode(Context $ctx, $nid)
  {
    $args = array(
      ':ip' => empty($_SERVER['REMOTE_ADDR']) ? null : $_SERVER['REMOTE_ADDR'],
      ':referer' => empty($_SERVER['HTTP_REFERER']) ? null : $_SERVER['HTTP_REFERER'],
      ':nid' => $nid,
      );

    $ctx->db->exec("INSERT INTO `node__astat` (`nid`, `timestamp`, `ip`, `referer`) VALUES (:nid, UTC_TIMESTAMP(), :ip, :referer)", $args);
  }
}
