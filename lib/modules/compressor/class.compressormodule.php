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
      $lscripts = array_merge($scripts, os::find($theme, 'scripts', '*.js'));
      $lstyles = array_merge($styles, os::find($theme, 'styles', '*.css'));

      os::write(os::path($theme, 'compressed.js'), self::join($lscripts, ';'));
      os::write(os::path($theme, 'compressed.css'), self::join($lstyles));
    }
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
    $data = preg_replace('@(url\(([^/][^)]+)\))@i', 'url('. dirname($filename) .'/\2)', $data);

    return $data;
  }
}
