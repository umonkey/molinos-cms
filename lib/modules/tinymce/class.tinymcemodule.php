<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TinyMceModule implements iPageHook
{
  public static function hookPage(&$output, Node $page)
  {
    if ('text/html' != $page->content_type)
      return;

    $config = mcms::modconf('tinymce');

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

  public static function add_extras()
  {
    $config = mcms::modconf('tinymce');

    if (empty($config['initializer']))
      $config['initializer'] = '';

    $text = 'script:tinyMCE_initializer = {'
      . $config['initializer']
      . ' };';
    mcms::extras($text);

    if (empty($config['gzip']))
      mcms::extras('lib/modules/tinymce/editor/tiny_mce.js', false);
    else
      mcms::extras('lib/modules/tinymce/editor/tiny_mce_gzip.js', false);

    if (empty($config['theme']))
      $config['theme'] = 'simple';

    switch ($config['theme']) {
    case 'simple':
    case 'medium':
    case 'advanced':
    case 'overkill':
      if (!empty($config['gzip']))
        mcms::extras('lib/modules/tinymce/editor/template_'.
          $config['theme'] .'_gzip.js');
      mcms::extras('lib/modules/tinymce/editor/template_'.
        $config['theme'] .'.js');
      break;
    default:
      mcms::flog($config['theme'] .': unknown theme');
    }
  }
}
