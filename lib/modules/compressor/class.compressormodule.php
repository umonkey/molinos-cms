<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CompressorModule implements /* iModuleConfig, */ iPageHook, iRequestHook, iRemoteCall
{
  private static function path()
  {
    return mcms::mkdir(mcms::config('tmpdir') .'/compressor');
  }

  public static function hookRemoteCall(RequestContext $ctx)
  {
    $type = $ctx->get('type');
    $fn = $ctx->get('hash');

    if (!file_exists($filepath = self::path() ."/mcms-{$fn}.{$type}"))
      throw new PageNotFoundException();

    if ($type == 'js')
      $type = "javascript";

    $maxAge = 3600 * 24;

    header('Expires: '. gmdate("D, d M Y H:i:s", time() + $maxAge) .' GMT');
    header('Pragma: cache');
    header('Cache-Control: public; max-age='. $maxAge);
    header("Content-type: text/{$type};charset: UTF-8");
    readfile($filepath);
    exit();
  }

  public static function hookPage(&$output, Node $page)
  {
    if ('text/html' != $page->content_type)
      return;

    $conf = array('options' => array('css', 'html'));

    if (self::can_touch_js())
      $conf['options'][] = 'js';

    /*
    $conf = mcms::modconf('compressor');

    if ((null === $conf) or empty($conf['options']) or !is_array($conf['options']))
      return;
    */

    if (in_array('js', $conf['options']))
      self::fixJS($output);

    if (in_array('css', $conf['options']))
      self::fixCSS($output);

    if (in_array('html', $conf['options']))
      self::fixHTML($output);
  }

  private static function can_touch_js()
  {
    if (!mcms::ismodule('smarty'))
      return false;
    if (!class_exists('BebopSmarty'))
      return false;
    return !BebopSmarty::debug();
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

  // Склеивает все локальные внешние скрипты в один файл, вырезает старые подключения,
  // вставляет новый скрипт в начало <head>.
  private static function fixJS(&$output)
  {
    $scripts = $names = array();

    if (preg_match_all('@<script\s+[^>]+></script>@i', $output, $m)) {
      foreach ($m[0] as $script) {
        $tmp = str_replace('"', "'", $script);

        if (false !== strstr($tmp, " language='javascript'")) {
          if (preg_match("@src='([^']+)'@i", $tmp, $m)) {
            if (false !== ($tmp = MCMS_ROOT .'/'. $m[1]) and '.js' == substr($tmp, -3) and '/' == substr($tmp, 0, 1)) {
              $names[] = '// '. $m[1] ."\n";
              $scripts[] = self::compressJS($tmp);
              $output = str_replace($script, '', $output);
            } else {
              mcms::log('compressor', 'skipped '. $m[1]);
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
      $scripts = array_unique($scripts);
      $md5name =  md5(join(',', $scripts));
      $filename = self::path() .'/mcms-'. $md5name.'.js';

      // Если файл с нужным именем не существует — создаём его.
      if (!file_exists($filename)) {
        $tmp = join('', $names) ."\n";

        foreach ($scripts as $f)
          $tmp .= file_get_contents($f); // .';';

        file_put_contents($filename, $tmp);
      }

      $newscript = mcms::html('script', array(
        'type' => 'text/javascript',
        'language' => 'javascript',
        'src' => '/compressor.rpc?type=js&hash='. $md5name,
        ));

      $output = str_replace('<head>', '<head>'. $newscript, $output);
    }
  }

  // Упаковывает указанный файл, возвращает его имя.
  private static function compressJS($filename)
  {
    $result = self::path() .'/mcms-'. md5($filename) .'.js';

    if (!file_exists($result) or (filemtime($filename) > filemtime($result)))
      file_put_contents($result, file_get_contents($filename));

    return $result;
  }

  private static function fixCSS(&$output)
  {
    $styles = $names = array();

    if (preg_match_all('@<link\s+[^>]*>@i', $output, $m)) {
      foreach ($m[0] as $link) {
        $tmp = str_replace('"', "'", $link);

        if (false !== strstr($tmp, "rel='stylesheet'")) {
          if (preg_match("@href='([^']+)'@i", $tmp, $m)) {
            if ('.css' == substr($m[1], -4)) {
              if (null !== ($ntmp = self::compressCSS($m[1]))) {
                $styles[] = $ntmp;
                $names[] = ' * '. $m[1] ."\n";
              }
              $output = str_replace($link, '', $output);
            } else {
              mcms::log('compressor', 'skipped '. $m[1]);
            }
          }
        }
      }
    }

    if (!empty($styles)) {
      $bulk = "/*\n". join('', $names) ." */\n\n";

      foreach (array_unique($styles) as $file) {
        if (file_exists($file)) {
          $tmp = file_get_contents($file);
          $bulk .= $tmp;
        } else {
          mcms::log('compressor', t('%file skipped — not found', array('%file' => $file)));
        }
      }

      $md5name = md5($bulk);
      $path = self::path() .'/mcms-'.$md5name .'.css';

      file_put_contents($path, $bulk);

      $newlink = mcms::html('link', array(
        'rel' => 'stylesheet',
        'type' => 'text/css',
        'href' => '/compressor.rpc?type=css&hash='. $md5name,
        ));

      $output = str_replace('</head>', $newlink .'</head>', $output);
    }
  }

  // Code taken from Kohana.
  private static function compressCSS($filename)
  {
    // Добавляем путь к сайту, если он не в корне.
    $filename = $filename;

    // Реальный путь к сжимаемому файлу.
    if (!file_exists($rpath = MCMS_ROOT .'/'. $filename)) {
      mcms::log('compressor', t('%file not found.', array('%file' => $filename)));
      return null;
    }

    // Путь к временному файлу.
    $result = self::path() .'/mcms-'. md5($filename) .'.css';

    if (!file_exists($result) or (filemtime($rpath) > filemtime($result))) {
      $data = file_get_contents($rpath);

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

      file_put_contents($result, $data);
    }

    return $result;
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
    if (file_exists($path = self::path() .'/'. $filename)) {
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
