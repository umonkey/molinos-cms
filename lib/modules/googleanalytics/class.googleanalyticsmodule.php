<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class GoogleAnalyticsModule implements iRequestHook
{
  public static function hookRequest(Context $ctx = null)
  {
    $conf = mcms::modconf('googleanalytics', 'config');
    if (!empty($conf['account'])) {
      $script = 'var gaJsHost = (("https:" == document.location.protocol) '
        . '? "https://ssl." : "http://www.");document.write(unescape("%3Cscript src=\'" + gaJsHost + '
        . '"google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));';
      $ctx->addExtra('script', $script);
      $script = 'try{var pageTracker = _gat._getTracker("' . $conf['account']
        . '");pageTracker._trackPageview();}catch(err){}';
      $ctx->addExtra('script', $script);
    }
  }
};
