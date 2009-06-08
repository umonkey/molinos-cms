<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class GoogleAnalyticsModule
{
  /**
   * Возвращает код для включения в страницу.
   * @mcms_message ru.molinos.cms.hook.pagecontent
   */
  public static function on_get_content(Context $ctx)
  {
    $conf = $ctx->config->get('modules/googleanalytics');
    if (!empty($conf['account'])) {
      $proto = empty($_SERVER['HTTPS'])
        ? 'http'
        : 'https';
      $output = html::em('script', array(
        'src' => $proto . '://google-analytics.com/ga.js',
        'type' => 'text/javascript',
        ));
      $output .= '<script type="text/javascript">try{var pageTracker = _gat._getTracker("' . $conf['account']
        . '");pageTracker._trackPageview();}catch(err){}</script>';

      return html::em('head', array(
        'module' => 'googleanalytics',
        ), html::cdata($output));
    }
  }
};
