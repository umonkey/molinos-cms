<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CompressorModule
{
  /**
   * @mcms_message ru.molinos.cms.install
   */
  public static function on_install(Context $ctx)
  {
    return self::on_reload($ctx);
  }

  /**
   * @mcms_message ru.molinos.cms.reload
   */
  public static function on_reload(Context $ctx)
  {
    $scripts = $styles = array();

    foreach ($ctx->registry->enum_simple('ru.molinos.cms.compressor.enum', array($ctx)) as $v1) {
      foreach ($v1 as $v2)
        if ('script' == $v2[0])
          $scripts[] = $v2[1];
        else
          $styles[] = $v2[1];
    }

    foreach (os::find('sites', '*', 'themes', '*') as $theme) {
      if (is_dir($theme)) {
        $lscripts = array_merge($scripts, os::find($theme, 'scripts', '*.js'));
        $lstyles = array_merge($styles, os::find($theme, 'styles', '*.css'));

        os::write(os::path($theme, 'compressed.js'), self::get_js_prefix($ctx) . self::join($lscripts, ';'));
        os::write(os::path($theme, 'compressed.css'), self::join($lstyles));
      }
    }

    self::on_reload_admin($ctx);
  }

  private static function on_reload_admin(Context $ctx)
  {
    $scripts = $styles = array();

    foreach ($ctx->registry->enum_simple('ru.molinos.cms.compressor.enum.admin', array($ctx)) as $v1) {
      foreach ($v1 as $v2)
        if ('script' == $v2[0])
          $scripts[] = $v2[1];
        else
          $styles[] = $v2[1];
    }

    $lscripts = array_merge($scripts, os::find('lib', 'modules', '*', 'scripts', 'admin', '*.js'));
    $lstyles = array_merge($styles, os::find('lib', 'modules', '*', 'styles', 'admin', '*.css'));

    $path = os::path(MCMS_SITE_FOLDER, 'themes', '.admin');

    os::write($path . '.js', self::get_js_prefix($ctx) . self::join($lscripts, ';'));
    os::write($path . '.css', self::join($lstyles));
  }

  private static function get_js_prefix(Context $ctx)
  {
    return "var mcms_path = '" . $ctx->folder() . "';\n"
      . "var mcms_url = 'http://" . MCMS_HOST_NAME . $ctx->folder() . "/';\n";
  }

  public static function join(array $filenames)
  {
    $output = '';

    foreach ($filenames as $filename) {
      if (file_exists($filename = MCMS_ROOT . DIRECTORY_SEPARATOR .  $filename)) {
        if (is_readable($filename)) {
          if ('.js' == substr($filename, -3))
            $output .= self::compressJS($filename);
          elseif ('.css' == substr($filename, '-4'))
            $output .= self::compressCSS($filename);
        }
      }
    }

    return $output;
  }

  private static function compressJS($filename)
  {
    return rtrim(file_get_contents($filename), ';') . ';';
  }

  // Code taken from Kohana.
  private static function compressCSS($filename)
  {
    $data = file_get_contents($filename);

    // Remove comments
    $data = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $data);

    // Remove tabs, spaces, newlines, etc.
    $data = preg_replace('/\s+/s', ' ', $data);
    $data = str_replace(
      array(' {', '{ ', ' }', '} ', ' +', '+ ', ' >', '> ', ' :', ': ', ' ;', '; ', ' ,', ', ', ';}'),
      array('{',  '{',  '}',  '}',  '+',  '+',  '>',  '>',  ':',  ':',  ';',  ';',  ',',  ',',  '}' ),
      $data);

    // Remove empty CSS declarations
    $data = preg_replace('/[^{}]++\{\}/', '', $data);

    // Fix relative url()
    $data = preg_replace('@(url\(([^/][^)]+)\))@i', 'url('. MCMS_WEB_FOLDER . '/' . os::webpath(os::localpath(dirname($filename))) .'/\2)', $data);

    return $data;
  }

  /**
   * Вывод инфорамации о подключаемых скриптах и стилях.
   * @mcms_message ru.molinos.cms.hook.pagecontent
   */
  public static function on_get_head(Context $ctx, array $pathinfo)
  {
    $output = '';
    $prefix = os::path(MCMS_SITE_FOLDER, 'themes', $pathinfo['theme'], 'compressed.');

    if (file_exists($fileName = $prefix . 'js'))
      $output .= html::em('script', array(
        'src' => os::webpath($fileName),
        'type' => 'text/javascript',
        ));
    if (file_exists($fileName = $prefix . 'css'))
      $output .= html::em('link', array(
        'rel' => 'stylesheet',
        'type' => 'text/css',
        'href' => os::webpath($fileName),
        ));

    return html::wrap('head', html::cdata($output), array(
      'module' => 'compressor',
      'weight' => 100,
      ));
  }
}
