<?php

require(dirname(__FILE__) .'/../lib/bootstrap.php');

$att = new StaticAttachment($_GET);
$att->sendFile();

class StaticAttachment
{
  var $folder;
  var $filename;
  var $source;
  var $output;
  var $nw;
  var $nh;
  var $options = array();
  var $node = null;

  public function __construct($get)
  {
    $args = explode(',', $get['q']);

    if (null === ($storage = BebopConfig::getInstance()->filestorage))
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
      print ": ".$more;

    exit();
  }

  public function sendFile()
  {
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

    if (!is_file($this->output) or filemtime($this->source) > filemtime($this->output)) {
      // Если файл существует -- обязательно его удаляем, иначе есть
      // шанс запороть оригинал, попытавшись перезаписать симлинк (по
      // причине кривой обработки параметров, или по другой -- не важно,
      // всякое бывает).
      if (is_link($this->output) and !unlink($this->output))
        $this->sendError(500, 'could not remove the file from cache. Overwriting that file would cause the original data to be lost.');

      // Масштабируем картинку, если это нужно.
      if ($this->nw !== null or $this->nh !== null)
        $this->resizeImage($img);

      // Если масштабирование не запрошено -- создаём симлинк на картинку,
      // чтобы больше к её обработке не возвращаться (загоняем в кэш).
      elseif (!symlink(getcwd() .'/'. $this->source, $this->output))
          $this->sendError(500, 'could not create a caching symlink for this attachment.');
    }

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

      if (!$img->save($this->output))
          $this->sendError(500, "could not write the resized image");
    } catch (Exception $e) {
        $this->sendError(500, $e->getMessage());
    }
  }

  private function sendFileDownload($img)
  {
    ini_set('zlib.output_compression', 0);

    $headers = array();
    $headers[] = "Content-Type: ". $this->node['filetype'];
    $headers[] = "Content-Length: ". $this->node['filesize'];

    $download = (strstr($this->node['filetype'], 'shockwave') === false);

    if ($download)
      $headers[] = "Content-Disposition: attachment; filename=\"{$this->node['filename']}\"";

    foreach ($headers as $item)
      header($item);

    $f = fopen($this->source, 'rb')
      or $this->sendError(403, "could not read the file");
    fpassthru($f);
  }
};
