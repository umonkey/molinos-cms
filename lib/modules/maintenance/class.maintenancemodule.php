<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class MaintenanceModule implements iRequestHook
{
  public static function hookRequest(Context $ctx = null)
  {
    if (null === $ctx) {
      $conf = mcms::modconf('maintenance');

      if (!empty($conf['state']) and 'closed' === $conf['state']) {
        $url = bebop_split_url();

        if ('admin' != substr($url['path'], 0, 7)) {
          $r = new Response(t('На сервере ведутся технические работы, обратитесь чуть позже.'), 'text/plain', 503);
          $r->send();
        }
      }
    }
  }
}
