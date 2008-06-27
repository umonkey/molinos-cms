<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AttachmentModule implements iRemoteCall
{
  static $folder;
  static $filename;
  static $source;
  static $output;
  static $ckey;
  static $nw;
  static $nh;
  static $options = array();
  static $node = null;
  static $realname = null;

  public static function hookRemoteCall(RequestContext $ctx)
  {
    $folder = $ctx->get('folder');
    if (empty($folder))
      $folder = "attachment";

    if (count($path = explode('/', $ctx->get('fid'))) > 1)
      self::$realname = $path[1];

    $args = explode(',', $path[0]);

    self::$ckey = 'image:'. $ctx->get('fid');

    // На данный момент у нас есть идентификатор картинки.
    // Пытаемся достать её из кэша, если получается — отдаём
    // пользователю и завершаем запрос.
    self::sendCache();

    if (null === ($storage = mcms::config('filestorage')))
      $storage = 'storage';

    $node = Node::load(array('class' => 'file', 'id' => $args[0]))->getRaw();
    if (empty($node))
      self::sendError(404, 'attachment not found.');

    self::$node = $node;

    self::$filename = $node['filename'];
    self::$source = $storage .'/'. $node['filepath'];

    self::$nw = !empty($args[1]) ? $args[1] : null;
    self::$nh = !empty($args[2]) ? $args[2] : null;

    if ((self::$nw !== null and !is_numeric(self::$nw)) or (self::$nh !== null and !is_numeric(self::$nh)))
        self::sendError(500, 'usage: '.$folder.'/filename[,width[,height]]');

    self::$folder = realpath(dirname(__FILE__) .'/'. $ctx->get('folder'));
    self::$output = self::$folder.'/'.$ctx->get('fid');

    if (!empty($args[3])) {
      for ($i = 0; $i < strlen($args[3]); $i++) {
        switch ($flag = substr($args[3], $i, 1)) {
        case 'c':
          self::$options['crop'] = true;
          break;
        case 'd':
          self::$options['downsize'] = true;
          break;
        case 'w':
          self::$options['white'] = true;
          break;
        case '0':
        case '1':
        case '2':
        case '3':
        case '4':
        case '5':
        case '6':
        case '7':
        case '8':
        case '9':
          self::$options['quality'] = (intval($flag) + 1) * 10;
          break;
        case '.':
          $i = strlen($args[3]);
          break;
        }
      }
    }
    self::sendFile();
    exit;
  }

  private function sendError($code, $more = null)
  {
    $codes = array(
      '200' => 'OK',
      '400' => 'Bad Request',
      '403' => 'Access Denied',
      '404' => 'Not Found',
      '415' => 'Bad Media Type',
      '500' => 'Internal Server Error',
      );

    if (!isset($codes[$code]))
      $code = 500;

    $text = $codes[$code];

    header("HTTP/1.1 {$code} {$text}");
    header("Content-Type: text/plain; charset=utf-8");
    print $text;

    if ($more !== null)
      print ": ". rtrim($more, '.') .'.';

    exit();
  }

  private function sendCache($data = null)
  {
    if (null === $data) {
      if (!is_array($data = mcms::cache(self::$ckey)))
        return;
    } else {
      //mcms::cache(self::$ckey, $data);
    }

    header('Content-Type: '. $data['type']);
    header('Content-Length: '. strlen($data['data']));
    die($data['data']);
  }

  public function sendFile()
  {
    self::sendCache();

    if (!is_file(self::$source))
      self::sendError(404, 'could not find this attachment.');

    if (!is_readable(self::$source))
      self::sendError(403, 'the file is not readable.');

    if (empty(self::$nw) and empty(self::$nh))
      return self::sendFileDownload();

    // Пытаемся открыть файл на чтение.  Сработает только если файл является картинкой.
    $img = ImageMagick::getInstance();
    $rc = $img->open(self::$source, self::$node['filetype']);

    // Если не удалось открыть, значит файл не является картинкой.  Отдаём его в режиме скачивания.
    if ($rc === false)
      return self::sendFileDownload();

    // Если получилось отмасштабировать — кэшируем и отдаём.
    if (is_array($tmp = self::resizeImage($img)))
      self::sendCache($tmp);

    // Открываем файл на чтение.
    $f = fopen(self::$output, 'r')
      or self::sendError(500, "could not read from cache.");

    // Отправляем файл клиенту.
    header('Content-Type: '.$img->getType());
    header('Content-Length: '.filesize(self::$output));
    fpassthru($f);
  }

  private function resizeImage($img)
  {
    try {
      if (!$img->scale(self::$nw, self::$nh, self::$options))
          self::sendError(500, "could not resize the image");

      if (!is_dir(self::$folder) and !mkdir(self::$folder))
          self::sendError(500, "could not create the cache directory: ".self::$folder);

      if (!is_writable(self::$folder))
          self::sendError(500, "could not cache the image.");

      if (!is_array($tmp = $img->dump()))
          self::sendError(500, "could not write the resized image");

      return $tmp;
    } catch (Exception $e) {
        self::sendError(500, $e->getMessage());
    }
  }

  private function sendFileDownload()
  {
    $headers = array();

    if (false === strstr(self::$node['filetype'], 'shockwave'))
      $download = false;
    elseif (false and substr(self::$node['filetype'], 0, 6) == 'image/')
      $download = false;
    else
      $download = true;

    if ($download and (null === self::$realname or self::$realname != self::$node['filename']))
      mcms::redirect("/attachment/".self::$node['id']."/". urlencode(self::$node['filename']));

    // Ещё раз загрузим файл для проверки прав.
    /*
    $node = Node::load(array('class' => 'file', 'id' => self::$node['id']));
    if (!$node->checkPermission('r'))
      self::sendError(403, 'access denied');
    */

    // Описание фрагмента при докачке.
    $range_from = 0;
    $range_to = self::$node['filesize'];

    if (!empty(self::$nw) or !empty(self::$nh)) {
      if ($f = fopen($fname = MCMS_ROOT .'/themes/admin/img/media-floppy.png', 'rb')) {
        header('Content-Type: image/png');
        header('Content-Length: '. filesize($fname));
        fpassthru($f);
        exit;
      } else {
        self::sendError(404, self::$node['filetype'] ." can not be resized");
      }
    }

    ini_set('zlib.output_compression', 0);

    if (!empty($_SERVER['HTTP_RANGE'])) {
      $range = substr($_SERVER['HTTP_RANGE'], strpos($_SERVER['HTTP_RANGE'], '=') + 1);
      $range_from = strtok($range, '-');
      $range_to = strtok('/');

      if (!empty($range_to))
        $range_to++;
      else
        $range_to = self::$node['filesize'];

      $headers[] = 'HTTP/1.1 206 Partial Content';
      $headers[] = 'Content-Range: bytes ' . $range_from . '-' . $range_to .'/'. self::$node['filesize'];
    } else {
      $headers[] = 'HTTP/1.1 200 OK';
    }

    $headers[] = "Content-Type: ". self::$node['filetype'];
    $headers[] = "Content-Length: ". ($range_to - $range_from);

    if ($download)
      $headers[] = "Content-Disposition: attachment; filename=\"".self::$node['filename']."\"";
/*
    // Клиентское кэширование, хотя не уверен, что это используется со скачиваемыми файлами.
    if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
      if (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= strtotime(self::$node['created'])) {
        self::sendError(304, 'not modified');
      }
    }
*/

    $headers[] = "Last-Modified: ". date('r', strtotime(self::$node['created']));
    $headers[] = "Accept-Ranges: bytes";

    foreach ($headers as $item)
      header($item);

    if ('GET' == $_SERVER['REQUEST_METHOD']) {
      if (!$range_from and mcms::ismodule('accesslog'))
        AccessLogModule::logNode(self::$node['id']);

      $f = fopen(self::$source, 'rb')
        or self::sendError(403, "could not read the file");

      if ($range_from)
        fseek($f, $range_from, SEEK_SET);

      if (empty($range_to))
        $size = self::$node['filesize'] - $range_from;
      else
        $size = $range_to;

      $sent = 0;

      while (!feof($f) and !connection_status() and ($sent < $size)) {
        echo fread($f, 512000);
        $sent += 512000;
        flush();
      }

      fclose($f);
    }
  }

};
