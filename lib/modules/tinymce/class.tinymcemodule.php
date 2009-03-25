<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TinyMceModule
{
  /**
   * @mcms_message ru.molinos.cms.hook.page
   */
  public static function hookPage(Context $ctx, &$output, Node $page)
  {
    if ('text/html' != $page->content_type)
      return;

    $config = $ctx->modconf('tinymce');

    $url = new url();

    if ('admin' != $url->path)
      if (empty($config['pages']) or !in_array($page->id, $config['pages']))
        return;

    if (false === strstr($output, 'visualEditor'))
      return;

    if (empty($config['gzip'])) {
      $html = html::em('script', array('src' => 'lib/modules/tinymce/editor/tiny_mce.js'));
    } else {
      $html = html::em('script', array('src' => 'lib/modules/tinymce/editor/tiny_mce_gzip.js'));
    }

    if (!strlen($tmp = self::getInit($config)))
      return;

    $html .= $tmp;

    $html .= html::em('script', array(
      'src' => 'lib/modules/tinymce/file_picker.js.php',
      ));

    if (!empty($html))
      $output = str_replace('</head>', $html .'</head>', $output);
  }

  /**
   * Возвращает список скриптов и стилей для использования на сайте.
   *
   * @mcms_message ru.molinos.cms.compressor.enum
   */
  public static function on_compressor_enum()
  {
    return array(
      array('script', 'lib/modules/tinymce/editor/tiny_mce_gzip.js'),
      array('script', 'lib/modules/tinymce/file_picker.js.php'),
      );
  }

  private static function getInit(array $config, $gzip = false)
  {
    $files = array();
    $path = dirname(__FILE__) .'/editor';

    switch ($config['theme']) {
    case 'simple':
    case 'medium':
    case 'advanced':
    case 'overkill':
      if (!empty($config['gzip']))
        $files[] = $path .'/template_'. $config['theme'] .'_gzip.js';
      $files[] = $path .'/template_'. $config['theme'] .'.js';
      break;
    }

    $output = '';

    foreach ($files as $f) {
      if (file_exists($f) and is_readable($f)) {
        $tmp = trim(file_get_contents($f));
        $tmp = preg_replace('/\s+/', ' ', $tmp);
        // $tmp = preg_replace('/([,:])\s+/', '\1', $tmp);
        $output .= '<script type=\'text/javascript\'>'. $tmp .'</script>';
      }
    }

    return $output;
  }

  public static function add_extras(Context $ctx)
  {
    $config = $ctx->modconf('tinymce');

    if (empty($config['initializer']))
      $config['initializer'] = '';

    $text = 'script:tinyMCE_initializer = {'
      . $config['initializer']
      . ' };';

    if (empty($config['gzip']))
      $ctx->addExtra('script', 'lib/modules/tinymce/editor/tiny_mce.js');
    else
      $ctx->addExtra('script', 'lib/modules/tinymce/editor/tiny_mce_gzip.js');

    if (empty($config['theme']))
      $config['theme'] = 'simple';

    switch ($config['theme']) {
    case 'simple':
    case 'medium':
    case 'advanced':
    case 'overkill':
      if (!empty($config['gzip']))
        $ctx->addExtra('script', 'lib/modules/tinymce/editor/template_'.
          $config['theme'] .'_gzip.js');
      $ctx->addExtra('script', 'lib/modules/tinymce/editor/template_'.
        $config['theme'] .'.js');
      break;
    default:
      mcms::flog($config['theme'] .': unknown theme');
    }
  }
}
