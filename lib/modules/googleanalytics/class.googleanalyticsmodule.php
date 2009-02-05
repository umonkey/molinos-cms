<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class GoogleAnalyticsModule implements iPageHook
{
  public static function hookPage(&$output, Node $page)
  {
    $config = mcms::modconf('googleanalytics');

    if (!empty($config['account'])) {
      $html = '<script type=\'text/javascript\' src=\'http://www.google-analytics.com/urchin.js\'></script>';
      $html .= "<script type='text/javascript'>";
      $html .= "_uacct = '{$config['account']}';";

      if (!empty($config['log_uids']))
        $html .= "__utmSetVar('". mcms_plain(Context::last()->user->getName()) ."');";

      $html .= "urchinTracker();";
      $html .= '</script>';

      $output = str_replace('</head>', $html .'</head>', $output);
    }
  }
};
