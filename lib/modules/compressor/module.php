<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CompressorModule implements iModuleConfig, iPageHook, iRequestHook
{
  public static function hookPage(&$output, Node $page)
  {
    if ('text/html' != $page->content_type)
      return;

    if ((null === ($conf = mcms::modconf('compressor'))) or empty($conf['options']) or !is_array($conf['options']))
      return;

    if (in_array('js', $conf['options']))
      self::fixJS($output);

    if (in_array('css', $conf['options']))
      self::fixCSS($output);

    if (in_array('html', $conf['options']))
      self::fixHTML($output);
  }

  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new SetControl(array(
      'value' => 'config_options',
      'label' => t('Компрессируемые объекты'),
      'options' => array(
        'js' => t('JavaScript'),
        'css' => t('Стили'),
        'html' => t('HTML'),
        ),
      )));

    $form->addControl(new BoolControl(array(
      'value' => 'config_gzip',
      'label' => t('Сжимать скрипты и стили с помощью gzip'),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }

  private static function fixJS(&$output)
  {
    $scripts = array();

    if (preg_match_all('@<script\s+[^>]+></script>@i', $output, $m)) {
      foreach ($m[0] as $script) {
        if ((false !== strstr($script, 'language="javascript"')) or (false !== strstr($script, "language='javascript'"))) {
          if (preg_match('@src="([^"]+)"@i', $script, $m) or preg_match("@src='([^']+)'@i", $script, $m)) {
            if (false !== ($tmp = realpath(getcwd() . $m[1])) and '.js' == substr($tmp, -3) and '/' == substr($tmp, 0, 1)) {
              $scripts[] = self::compressJS($tmp);
              $output = str_replace($script, '', $output);
            }
          }
        }
      }
    }

    // На этот момент в массиве $script содержатся имена уже упакованных скриптов,
    // которые нужно склеить и выдать клиенту.  Т.к. имена этих файлов формируются
    // с учётом времени изменения, для получения имени результирующего файла можно
    // просто склеить их и взять сумму.  Это поможет избежать лишних склеиваний.

    if (!empty($scripts)) {
      $filename = mcms::config('filestorage') .'/mcms-'. md5(join(',', $scripts)) .'.js';

      // Если файл с нужным именем не существует — создаём его.
      if (!file_exists($filename)) {
        $tmp = '';

        foreach ($scripts as $f)
          $tmp .= file_get_contents($f); // .';';

        file_put_contents($filename, $tmp);
      }

      $output = str_replace('</head>', "<script type='text/javascript' language='javascript' src='/{$filename}'></script></head>", $output);
    }
  }

  // Упаковывает указанный файл, возвращает его имя.
  private static function compressJS($filename)
  {
    $result = mcms::config('filestorage') .'/mcms-'. md5($filename .'.'. filemtime($filename)) .'.js';

    if (!file_exists($result)) {
      require_once(dirname(__FILE__) .'/jsmin-1.1.0.php');
      file_put_contents($result, JSMin::minify(file_get_contents($filename)));
    }

    return $result;
  }

  private static function fixCSS(&$output)
  {
    $styles = array();

    if (preg_match_all('@<link\s+[^>]*>@i', $output, $m)) {
      foreach ($m[0] as $link) {
        if ((false !== strstr($link, 'rel="stylesheet"')) or (false !== strstr($link, "rel='stylesheet'"))) {
          if (preg_match('@href="([^"]+)"@i', $link, $m) or preg_match("@href='([^']+)'@i", $link, $m)) {
            if (false !== ($tmp = self::fixPath($m[1], '.css'))) {
              $styles[] = $tmp;
              $output = str_replace($link, '', $output);
            }
          }
        }
      }
    }

    if (!empty($styles)) {
      $bulk = '';

      foreach ($styles as $file) {
        if (file_exists($filename = getcwd() . $file)) {
          $tmp = file_get_contents($filename);
          $tmp = preg_replace('@(url\(([^/][^)]+)\))@i', 'url('. dirname($file) .'/\2)', $tmp);
          $bulk .= $tmp;
        }
      }

      $bulk = self::compressCSS($bulk);

      $path = mcms::config('filestorage') .'/mcms-'. md5($bulk) .'.css';

      file_put_contents($path, $bulk);

      $output = str_replace('</head>', "<link rel='stylesheet' type='text/css' href='/{$path}' /></head>", $output);
    }
  }

  // Code taken from Kohana.
  private static function compressCSS($output)
  {
    // Remove comments
    $output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $output);

    // Remove tabs, spaces, newlines, etc.
    $output = preg_replace('/\s+/s', ' ', $output);
    $output = str_replace(
      array(' {', '{ ', ' }', '} ', ' +', '+ ', ' >', '> ', ' :', ': ', ' ;', '; ', ' ,', ', ', ';}'),
      array('{',  '{',  '}',  '}',  '+',  '+',  '>',  '>',  ':',  ':',  ';',  ';',  ',',  ',',  '}' ),
      $output);

    // Remove empty CSS declarations
    $output = preg_replace('/[^{}]++\{\}/', '', $output);

    return $output;
  }

  private static function fixHTML(&$output)
  {
    $output = preg_replace('@>\s+<@i', '><', $output);
  }

  private static function fixPath($path, $ext)
  {
    if ('/' != substr($path, 0, 1))
      return false;

    if ($ext != substr($path, - strlen($ext)))
      return false;

    if (false === strstr($path, '..'))
      return $path;

    $args = explode('/', ltrim($path, '/'));

    while (in_array('..', $args)) {
      foreach ($args as $k => $v) {
        if ('..' == $v) {
          if ($k == 0)
            return null;
          else {
            unset($args[$k]);
            unset($args[$k - 1]);
          }
        }
      }
    }

    return '/'. join('/', $args);
  }

  public static function hookRequest(RequestContext $ctx = null)
  {
    if (null === $ctx and preg_match('@^/extras/(mcms-[0-9a-z]+\.(js|css))$@', $_SERVER['REQUEST_URI'], $m))
      self::serveFile($m[1]);
  }

  private static function serveFile($filename)
  {
    if (file_exists($path = mcms::config('filestorage') .'/'. $filename)) {
      $data = file_get_contents($path);

      header('HTTP/1.1 200 OK');

      if ('.css' == substr($filename, -4))
        header('Content-Type: text/css; charset=utf-8');
      elseif ('.js' == substr($filename, -3))
        header('Content-Type: text/javascript; charset=utf-8');
      else
        return;

      // Немного агрессивного кэширования, источник:
      // http://www.thinkvitamin.com/features/webapps/serving-javascript-fast

      header("Expires: ".gmdate("D, d M Y H:i:s", time()+315360000)." GMT");
      header("Cache-Control: max-age=315360000");

      if (ini_get('zlib.output_compression') or !function_exists('ob_gzhandler'))
        die($data);

      if (false === ($zipped = ob_gzhandler($data, PHP_OUTPUT_HANDLER_START)))
        die($data);

      header('Content-Length: '. strlen($zipped));

      die($data);
    }
  }
}
