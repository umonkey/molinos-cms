<?php

class GoogleAjaxSearch
{
  public static function on_get_api(Context $ctx)
  {
    $output = '';

    if (($query = $ctx->get('query')) and ($key = $ctx->config->get('modules/search/gas_key'))) {
      $query = str_replace('"', "'", $query);

      $paths = array(
        os::path(MCMS_SITE_FOLDER, 'gas.js'),
        os::path('lib', 'modules', 'search', 'gas.js'),
        );

      foreach ($paths as $path) {
        if (file_exists($path)) {
          $js = str_replace('QUERY', $query, file_get_contents($path));
          $js = str_replace('HOSTNAME', url::host($_SERVER['HTTP_HOST']), $js);

          $output = '<script type="text/javascript" src="http://www.google.com/jsapi?key=' . $key . '"></script>'
            . '<script type="text/javascript">' . $js . '</script>';
          break;
        }
      }
    }

    return new Response(html::em('gas', html::cdata($output)), 'text/xml');
  }
}
