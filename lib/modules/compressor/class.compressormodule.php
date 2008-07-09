<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CompressorModule implements iRemoteCall
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

    self::serveFile($filepath);
  }

  private static function serveFile($filename)
  {
    $data = file_get_contents($filename);

    // header('HTTP/1.1 200 OK');

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
    header('Content-Length: '. strlen($data));

    die($data);
  }

  private static function formatJS(array $files)
  {
    $scripts = $names = array();

    foreach ($files as $file => $ok) {
      if (!$ok)
        continue;

      if ('.js' == substr($file, -3)) {
        $url = new url($file);

        if (empty($url->host)) {
          if (file_exists($fullname = MCMS_ROOT .'/'. $url->path)) {
            $names[] = "// {$file}\n";
            $scripts[] = self::compressJS($fullname);
          } else {
            mcms::log('compressor', $file .': dead, skipped');
          }
        }
      }
    }

    // На этот момент в массиве $script содержатся имена уже упакованных
    // скриптов, которые нужно склеить и выдать клиенту.  Т.к. имена этих
    // файлов формируются с учётом времени изменения, для получения имени
    // результирующего файла можно просто склеить их и взять сумму.
    // Это поможет избежать лишних склеиваний.

    if (!empty($scripts)) {
      $filename = self::mkname($scripts, '.js', $md5name);

      // Если файл с нужным именем не существует — создаём его.
      if (!file_exists($filename)) {
        $bulk = join('', $names) ."\n";

        foreach ($scripts as $f)
          $bulk .= file_get_contents($f);

        file_put_contents($filename, $bulk);
      }

      $newscript = mcms::html('script', array(
        'type' => 'text/javascript',
        'src' => 'compressor.rpc?type=js&hash='. $md5name,
        )) ."\n";

      return $newscript;
    }
  }

  // Упаковывает указанный файл, возвращает его имя.
  private static function compressJS($filename)
  {
    $result = self::path() .'/mcms-'. md5($filename) .'.js';

    if (!file_exists($result) or (filemtime($filename) > filemtime($result))) {
      $header = "\n// {$filename}\n";
      file_put_contents($result, $header . file_get_contents($filename));
    }

    return $result;
  }

  private static function formatCSS(array $files)
  {
    $styles = $names = array();

    foreach ($files as $file => $ok) {
      if (!$ok)
        continue;

      $url = new url($file);

      if (empty($url->host)) {
        if ('.css' == substr($url->path, -4)) {
          $fullname = MCMS_ROOT .'/'. $url->path;

          if (is_readable($fullname)) {
            $names[] = " * {$file}\n";
            $styles[] = self::compressCSS($file);
          } else {
            mcms::log('compressor', $file .': dead, skipped');
          }
        }
      }
    }

    if (!empty($styles)) {
      $filename = self::mkname($styles, '.css', $md5name);

      if (!file_exists($filename)) {
        $bulk = "/*\n". join('', $names) ." */\n\n";

        foreach ($styles as $file)
          $bulk .= file_get_contents($file);

        file_put_contents($filename, $bulk);
      }

      $newlink = mcms::html('link', array(
        'rel' => 'stylesheet',
        'type' => 'text/css',
        'href' => 'compressor.rpc?type=css&hash='. $md5name,
        )) ."\n";

      return $newlink;
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

  private static function mkname(array $files, $suffix, &$md5name)
  {
    $items = array();

    foreach (array_unique($files) as $file)
      $items[] = $file .','. filemtime($file);

    sort($items);

    $md5name = md5(join(',', $items));

    return self::path() .'/mcms-'. $md5name . $suffix;
  }

  public static function format(array $files)
  {
    $output = self::formatJS($files);
    $output .= self::formatCSS($files);
    return $output;
  }
}
