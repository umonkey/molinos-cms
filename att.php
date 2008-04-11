<?php

require(dirname(__FILE__) .'/lib/bootstrap.php');

$att = new StaticAttachment($_GET);
$att->sendFile();

class StaticAttachment
{
  var $folder;
  var $filename;
  var $source;
  var $output;
  var $ckey;
  var $nw;
  var $nh;
  var $options = array();
  var $node = null;
  var $realname = null;

  public function __construct($get)
  {
    if (count($path = explode('/', $get['q'])) > 1)
      $this->realname = $path[1];

    $args = explode(',', $path[0]);

    $this->ckey = 'image:'. $get['q'];

    // На данный момент у нас есть идентификатор картинки.
    // Пытаемся достать её из кэша, если получается — отдаём
    // пользователю и завершаем запрос.
    $this->sendCache();

    if (null === ($storage = mcms::config('filestorage')))
      $storage = 'storage';

    $node = Tagger::getInstance()->getObject($args[0]);
    if (empty($node))
      $this->sendError(404, 'attachment not found.');

    $this->node = $node;

    $this->filename = $node['filename'];
    $this->source = $storage .'/'. $node['filepath'];

    $this->nw = !empty($args[1]) ? $args[1] : null;
    $this->nh = !empty($args[2]) ? $args[2] : null;

    if (($this->nw !== null and !is_numeric($this->nw)) or ($this->nh !== null and !is_numeric($this->nh)))
        $this->sendError(500, 'usage: '.$get['folder'].'/filename[,width[,height]]');

    $this->folder = realpath(dirname(__FILE__) .'/'. $_GET['folder']);
    $this->output = $this->folder.'/'.$get['q'];

    if (!empty($args[3])) {
      for ($i = 0; $i < strlen($args[3]); $i++) {
        switch ($flag = substr($args[3], $i, 1)) {
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
          $i = strlen($args[3]);
          break;
        }
      }
    }
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
      if (!is_array($tmp = mcms::cache($this->ckey)))
        return;
    } else {
      mcms::cache($this->ckey, $data);
    }

    header('Content-Type: '. $tmp['type']);
    header('Content-Length: '. strlen($tmp['data']));
    die($tmp['data']);
  }

  public function sendFile()
  {
    $this->sendCache();

    bebop_debug();

    if (!is_file($this->source))
      $this->sendError(404, 'could not find this attachment.');

    if (!is_readable($this->source))
      $this->sendError(403, 'the file is not readable.');

    // Пытаемся открыть файл на чтение.  Сработает только если файл является картинкой.
    $img = ImageMagick::getInstance();
    $rc = $img->open($this->source, $this->node['filetype']);

    // Если не удалось открыть, значит файл не является картинкой.  Отдаём его в режиме скачивания.
    if ($rc === false)
      return $this->sendFileDownload($img);

    // Если получилось отмасштабировать — кэшируем и отдаём.
    if (is_array($tmp = $this->resizeImage($img)))
      $this->sendCache($tmp);

    // Открываем файл на чтение.
    $f = fopen($this->output, 'r')
      or $this->sendError(500, "could not read from cache.");

    // Отправляем файл клиенту.
    header('Content-Type: '.$img->getType());
    header('Content-Length: '.filesize($this->output));
    fpassthru($f);
  }

  private function resizeImage($img)
  {
    try {
      if (!$img->scale($this->nw, $this->nh, $this->options))
          $this->sendError(500, "could not resize the image");

      if (!is_dir($this->folder) and !mkdir($this->folder))
          $this->sendError(500, "could not create the cache directory: ".$this->folder);

      if (!is_writable($this->folder))
          $this->sendError(500, "could not cache the image.");

      if (!is_array($tmp = $img->dump()))
          $this->sendError(500, "could not write the resized image");

      return $tmp;
    } catch (Exception $e) {
        $this->sendError(500, $e->getMessage());
    }
  }

  private function sendFileDownload($img)
  {
    $headers = array();

    if (null === $this->realname or $this->realname != $this->node['filename'])
      bebop_redirect("/attachment/{$this->node['id']}/". urlencode($this->node['filename']));

    // Ещё раз загрузим файл для проверки прав.
    $node = Node::load(array('class' => 'file', 'id' => $this->node['id']));
    if (!$node->checkPermission('r'))
      $this->sendError(403, 'access denied');

    // Описание фрагмента при докачке.
    $range_from = 0;
    $range_to = $this->node['filesize'];

    if (!empty($this->nw) or !empty($this->nh))
      $this->sendError(404, $this->node['filetype'] ." can not be resized");

    ini_set('zlib.output_compression', 0);

    if (!empty($_SERVER['HTTP_RANGE'])) {
      $range = substr($_SERVER['HTTP_RANGE'], strpos($_SERVER['HTTP_RANGE'], '=') + 1);
      $range_from = strtok($range, '-');
      $range_to = strtok('/');

      if (!empty($range_to))
        $range_to++;
      else
        $range_to = $this->node['filesize'];

      $headers[] = 'HTTP/1.1 206 Partial Content';
      $headers[] = 'Content-Range: bytes ' . $range_from . '-' . $range_to .'/'. $this->node['filesize'];
    } else {
      $headers[] = 'HTTP/1.1 200 OK';
    }

    $headers[] = "Content-Type: ". $this->node['filetype'];
    $headers[] = "Content-Length: ". ($range_to - $range_from);

    $download = (strstr($this->node['filetype'], 'shockwave') === false);

    if ($download)
      $headers[] = "Content-Disposition: attachment; filename=\"{$this->node['filename']}\"";

    // Клиентское кэширование, хотя не уверен, что это используется со скачиваемыми файлами.
    if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
      if (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= strtotime($this->node['created'])) {
        $this->sendError(304, 'not modified');
      }
    }

    $headers[] = "Last-Modified: ". date('r', strtotime($this->node['created']));
    $headers[] = "Accept-Ranges: bytes";

    foreach ($headers as $item)
      header($item);

    if ('GET' == $_SERVER['REQUEST_METHOD']) {
      if (!$range_from and class_exists('AccessLogModule'))
        AccessLogModule::logNode($this->node['id']);

      $f = fopen($this->source, 'rb')
        or $this->sendError(403, "could not read the file");

      if ($range_from)
        fseek($f, $range_from, SEEK_SET);

      if (empty($range_to))
        $size = $this->node['filesize'] - $range_from;
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
