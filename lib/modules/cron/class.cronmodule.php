<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CronModule
{
  /**
   * Запуск периодических задач.
   *
   * Проверяется время последнего запуска, чаще установленного администратором
   * времени запуск производиться не будет (по умолчанию 15 минут).
   */
  public static function on_rpc(Context $ctx)
  {
    $status = "DELAYED";

    $lastrun = $ctx->config->get('modules/cron/lastrun');
    $delay = $ctx->config->get('modules/cron/delay', 15) * 60;

    if (time() >= $lastrun + $delay) {
      $ctx->config->set('modules/cron/lastrun', time());
      $ctx->config->save();

      @set_time_limit(0);

      ob_start();
      try {
        $ctx->registry->broadcast('ru.molinos.cms.cron', array($ctx));
        $status = "OK";
      } catch (Exception $e) {
        Logger::trace($e);
        $status = "ERROR: " . get_class($e) . '; ' . trim($e->getMessage(), '.') . '.';
      }
      ob_end_clean();
    }

    if ($ctx->get('destination'))
      return $ctx->getRedirect();

    if (!MCMS_CONSOLE) {
      header('Content-Type: text/plain; charset=utf-8');
      die($status);
    }

    die();
  }
};
