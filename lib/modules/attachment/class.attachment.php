<?php

class Attachment
{
  private $folder;
  private $filename;
  private $source;
  private $output;
  private $nw;
  private $nh;
  private $options = array();
  private $node = null;
  private $guid = null;
  private $ctx = null;

  public function __construct(Context $ctx)
  {
    if (preg_match('@attachment/(\d+)(?:,([a-z0-9,.]*))?(?:/(.*))?$@', $ctx->query(), $m)) {
      if (!empty($m[2])) {
        $parts = explode(',', $m[2]);

        if (!empty($parts[0]))
          $this->nw = $parts[0];
        if (!empty($parts[1]))
          $this->nh = $parts[1];

        // TODO: cdw, другие опции
      }

      $this->filename = $m[3];

      $fid = $m[1];
    }

    elseif (preg_match('@^(\d+)(?:,(\d*))?(?:,(\d*))?(?:,([^/]+))?(?:/(.+))?@', $q = $ctx->get('fid'), $m)) {
      $this->nw = empty($m[2]) ? null : $m[2];
      $this->nh = empty($m[3]) ? null : $m[3];
      $this->filename = empty($m[5]) ? null : $m[5];

      $fid = $m[1];
    }
    
    else {
      mcms::fatal('Usage: ?q=attachment.rpc&fid=id[,width[,height[,options[/filename]]]]');
    }

    $this->ctx = $ctx;

    try {
      $this->node = Node::load(array(
        'class' => 'file',
        'id' => $fid,
        ));
    } catch (ObjectNotFoundException $e) {
      $this->sendError(404, 'file not found.');
    }

    if (!empty($this->filename) and $this->filename != $this->node->filename)
      $this->sendError(404, 'file "'. $this->filename .'" not found.');

    $this->parseOptions($m[4]);
  }

  private function parseOptions($opt)
  {
    for ($i = 0; $i < strlen($opt); $i++) {
      switch ($flag = substr($opt, $i, 1)) {
      case 'c':
        $this->options['crop'] = true;
        break;
      case 'd':
        $this->options['downsize'] = true;
        break;
      case 'w':
        $this->options['white'] = true;
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
        $this->options['quality'] = (intval($flag) + 1) * 10;
        break;
      case '.':
        $i = strlen($opt);
        break;
      }
    }
  }

  private function sendError($code, $more = null)
  {
    if (ob_get_length())
      ob_end_clean();

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

    die();
  }

  /**
   * Выдача файла клиенту.
   */
  public function sendFile()
  {
    if (!$this->sendImage())
      $this->sendDownload();
    die();
  }

  private function sendImage()
  {
    // Масштабирования нет — отдаём исходный файл.
    if (empty($this->nw) and empty($this->nh))
      return false;

    if (null === ($guid = $this->getGuid()))
      return false;

    // Отсеиваем немасштабируемые картинки.
    if (!$this->isResizable())
      return false;

    if (!file_exists($guid)) {
      ob_start();

      $img = ImageMagick::getInstance();
      $rc = $img->open($this->getSourceFile(), $this->node->filetype);

      ob_end_clean();

      // Если не удалось открыть, значит файл не является картинкой.  Отдаём его в режиме скачивания.
      if ($rc === false)
        $this->sendError('500', 'this file must be an image, but it isn\'t.');

      if (!is_array($tmp = $this->resizeImage($img)))
        $this->sendError(500, 'failed to resize the image.');
      else
        file_put_contents($guid, $tmp['data']);
    }

    header('Content-Type: '. $this->node->filetype);
    header('Content-Length: '. filesize($guid));

    die(fpassthru(fopen($guid, 'rb')));
  }

  private function sendDownload()
  {
    $headers = array();

    if (!file_exists($this->getSourceFile()))
      $this->sendError(404, 'Ошибка: файл не найден в файловом архиве.');

    if (false !== strstr($this->node->filetype, 'shockwave'))
      $download = false;
    elseif ($this->isResizable())
      $download = false;
    elseif ($this->isImage()) {
      $download = true;
      $this->node->filetype = 'octet/stream';
    } else
      $download = true;

    if ($download and empty($this->nw) and empty($this->nh)) {
      if ($this->filename != $this->node->filename) {
        $path = 'attachment/'. $this->node->id .'/'.
          urlencode($this->node->filename);
        $this->ctx->redirect($path);
      }
    }

    // Описание фрагмента при докачке.
    $range_from = 0;
    $range_to = $this->node->filesize;

    if (!empty($this->nw) or !empty($this->nh)) {
      $sig = str_replace('/', '-', $this->node->filetype);
      if (!file_exists($icon = dirname(__FILE__) .'/mime/'. $sig .'.png'))
        $icon = dirname(__FILE__) .'/mime/application-octet-stream.png';
      $this->send($icon, 'image/png');
    }

    ini_set('zlib.output_compression', 0);

    if (!empty($_SERVER['HTTP_RANGE'])) {
      $range = substr($_SERVER['HTTP_RANGE'], strpos($_SERVER['HTTP_RANGE'], '=') + 1);
      $range_from = strtok($range, '-');
      $range_to = strtok('/');

      if (!empty($range_to))
        $range_to++;
      else
        $range_to = $this->node->filesize;

      $headers[] = 'HTTP/1.1 206 Partial Content';
      $headers[] = 'Content-Range: bytes ' . $range_from . '-' . $range_to
        .'/'.  $this->node->filesize;
    } else {
      $headers[] = 'HTTP/1.1 200 OK';
    }

    $headers[] = "Content-Type: ". $this->node->filetype;
    $headers[] = "Content-Length: ". ($range_to - $range_from);

    if ($download) {
      $filename = $this->node->filename;

      if (function_exists('mb_convert_encoding'))
        if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'compatible; MSIE'))
          $filename = mb_convert_encoding($filename, 'windows-1251', 'utf-8');

      if ('text/plain' != $this->node->filetype)
        $headers[] = "Content-Disposition: attachment; "
          ."filename=\"". $filename ."\"";
    }

    /*
    // Клиентское кэширование, хотя не уверен, что это используется со скачиваемыми файлами.
    if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
      if (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= strtotime($this->node['created'])) {
        $this->sendError(304, 'not modified');
      }
    }
    */

    $headers[] = "Last-Modified: ". date('r', strtotime($this->node->created));
    $headers[] = "Accept-Ranges: bytes";

    foreach ($headers as $item)
      header($item);

    if ('GET' == $_SERVER['REQUEST_METHOD']) {
      if (!$range_from and mcms::ismodule('accesslog'))
        AccessLogModule::logNode($this->node['id']);

      $f = fopen($this->getSourceFile(), 'rb')
        or $this->sendError(403, "could not read the file");

      if ($range_from)
        fseek($f, $range_from, SEEK_SET);

      if (empty($range_to))
        $size = $this->node->filesize - $range_from;
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

  /**
   * Масштабирование картинки.
   *
   * Возвращает массив с ключами data и type.
   */
  private function resizeImage($img)
  {
    try {
      if (!$img->scale($this->nw, $this->nh, $this->options))
        $this->sendError(500, "could not resize the image");

      if (!is_array($tmp = $img->dump()))
        $this->sendError(500, "could not write the resized image");

      return $tmp;
    } catch (Exception $e) {
      $this->sendError(500, $e->getMessage());
    }
  }

  /**
   * Возвращает уникальный идентификатор изображения.
   *
   * Идентификатор составляется из id ноды/ревизии и параметров масштабирования.
   * Используется для кэширования.
   */
  private function getGuid()
  {
    if (0 !== strpos($this->node->filetype, 'image/'))
      return null;

    $args = join(',', array(
      $this->node->id,
      $this->node->rid,
      $this->nw,
      $this->nh,
      join('+', array_keys($this->options)),
      $this->node->filename,
      ));

    $guid = mcms::mkdir(mcms::config('tmpdir') .'/images')
      .'/file-'. md5($args) . strrchr($this->node->filename, '.');

    return $guid;
  }

  private function getSourceFile()
  {
    return mcms::config('filestorage') .'/'. $this->node->filepath;
  }

  /**
   * Выдача файла клиенту.
   *
   * Выдаёт заголовки Content-Type и Content-Length.
   */
  private function send($fname, $type)
  {
    if ($f = fopen($fname, 'rb')) {
      header('Content-Type: '. $type);
      header('Content-Length: '. filesize($fname));
      fpassthru($f);
      exit();
    }

    $this->sendError(500, 'could not dump the file.');
  }

  private function isImage()
  {
    return 0 === strpos($this->node->filetype, 'image/');
  }

  private function isResizable()
  {
    if ($this->isImage()) {
      switch ($this->node->filetype) {
        case 'image/jpeg':
        case 'image/pjpeg':
        case 'image/png':
        case 'image/gif':
          return true;
      }
    }

    return false;
  }
};
