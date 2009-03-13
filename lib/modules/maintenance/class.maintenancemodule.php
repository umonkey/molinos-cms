<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class MaintenanceModule
{
  /**
   * @mcms_message ru.molinos.cms.hook.request.before
   */
  public static function hookRequest(Context $ctx)
  {
    $conf = mcms::modconf('maintenance');

    if (!empty($conf['state']) and 'closed' === $conf['state'] and !$ctx->canDebug()) {
      $query = $ctx->query();

      if ('admin' != $query and 0 !== strpos($query, 'admin/')) {
        $r = new Response(t('На сервере ведутся технические работы, обратитесь чуть позже.'), 'text/plain', 503);
        $r->send();
      }
    }
  }
}
