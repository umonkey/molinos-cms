<?php

class ImageEdit implements iRemoteCall
{
  private $folder;
  private $filename;
  private $source;
  private $output;
  private $nw;
  private $nh;

  public static function hookRemoteCall(RequestContext $ctx)
  {
    $img = new ImageEdit($ctx);
    $img->sendFile();
    exit();
  }

  // Набор параметров, которые используются для редактирования изображения,
  // подробности - в документации
  private $options = array(
      'fid' => null,
      'scale' => 100,
      'mirrorV' => false,
      'mirrorH' => false,
      'angle' => 0,
      'offsetX' => 0,
      'offsetY' => 0,
      'cropLeft' => 0,
      'cropTop' => 0,
      'cropRight' => 0,
      'cropBottom' => 0,
      'merge' => null,
      'noshow' => false,
  );

  private $node = null;
  private $mergeNode = null;

  public function __construct(RequestContext $ctx)
  {
    // Если кроме id аттачмента ничего не задали, ругаемся
    if (null == $ctx->get('fid', null) or null == $ctx->get('scale', null))
    	$this->sendError(500, 'usage: imgtoolkit.rpc&fid=attachment[&operation=parameter...]');

    foreach ($this->options as $k => $v) {
      if ($p = $ctx->get($k)) {
      	if (in_array($k, array('noshow', 'merge', 'fid', 'mirrorH', 'mirrorV')))
	        $this->options[$k] = $p;
        else
	        $this->options[$k] = floatval($p);
      }
      // Формируем имя файла-ссылки для кэша
      $outfile .= "{$this->options[$k]},";
    }

    $outfile = trim($outfile, ',');
    $node = Node::load(array('class' => 'file', 'id' => $this->options['fid']));

    if (empty($node))
      self::sendError(404, 'attachment not found.');

    if (null === ($storage = mcms::config('filestorage')))
      $storage = 'storage';

    $this->node = $node;
    $this->filename = $node->filename;
    $this->folder = $storage;
    $this->sourceDir = $storage;
    $this->source = $storage . '/' . $node->filepath;
    $this->output = $this->folder . '/' . $outfile;

    if (null != $this->options['merge']) {
	    $this->mergeNode = Node::load(array('class' => 'file', 'id' => $this->options['merge']));
	    if (empty($this->mergeNode))
	    	self::sendError(404, 'merge attachment not found.');
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
    if (!is_file($this->source) or !is_readable($this->source))
      $this->sendError(404, 'could not find this attachment.');

    // Пытаемся открыть файл на чтение.  Сработает только если файл является картинкой.
    $img = ImageMagick::getInstance();
    $rc = $img->open($this->source, $this->node->filetype);
    
    // Если не удалось открыть, значит файл не является картинкой.  Отдаём его в режиме скачивания.
    if ($rc === false)
      return $this->sendFileDownload($img);

    if (!is_file($this->output) or filemtime($this->source) > filemtime($this->output)) {
      // Если файл существует -- обязательно его удаляем, иначе есть
      // шанс запороть оригинал, попытавшись перезаписать симлинк (по
      // причине кривой обработки параметров, или по другой -- не важно,
      // всякое бывает).

      // Масштабируем картинку, если это нужно.
      if ($this->options !== null)
        $this->processImage($img);

      if (is_link($this->output) and !unlink($this->output))
        $this->sendError(500, 'could not remove the file from cache. Overwriting that file would cause the original data to be lost.');
    }

    // Бывают ситуации, когда картинку не нужно отдавать в браузер,
    // чтобы не заставлять клиента грузить ненужные килобайты, а то и мегабайты.
    if (false == $this->options['noshow']) {
    	// Открываем файл на чтение.
    	$f = fopen($this->output, 'r')
    	or $this->sendError(500, "could not read from cache.");

    	// Отправляем файл клиенту.
    	header('Content-Type: '.$img->getType());
    	header('Content-Length: '.filesize($this->output));
    	fpassthru($f);
    } else {
    	exit();
    }
  }

  private function processImage($img)
  {
    try {
      $origSize = $img->getImageSize();

      // Масштабирование
      $nw = intval($origSize[0] * $this->options['scale'] / 100);
      $nh = intval($origSize[1] * $this->options['scale'] / 100);

      if (!$img->scale($nw, $nh))
        $this->sendError(500, "could not resize the image");

      // Отзеркаливание по вертикали
      if (true == $this->options['mirrorV'])
        $img->mirror('v');

      // Отзеркаливание по горизонтали
      if (true == $this->options['mirrorH'])
        $img->mirror('h');

      // Поворот на заданный угол
      $angle = $this->options['angle'];
      if (is_numeric($angle) && ($angle != 360 or $angle != 0))
        $img->rotate(-($angle));

      // Смещение полотна
      $offsetX = $this->options['offsetX'];
      $offsetY = $this->options['offsetY'];

      if (is_numeric($offsetX) && is_numeric($offsetY)) {
        $img->moveTo($offsetX, $offsetY);
      }

      // Кадрирование по заданным габаритам
      $size = $img->getImageSize();
      $dX = $this->options['cropLeft'];
      $dY = $this->options['cropTop'];
      $dW = $this->options['cropRight'];
      $dH = $this->options['cropBottom'];

      $nW = $size[0] - $dW - $dX;
      $nH = $size[1] - $dH - $dY;

      if (!$img->crop($dX, $dY, $nW, $nH))
        $this->sendError(500, "could not crop the image");

      if (null != $this->mergeNode) {
        if (!$img->watermark($this->sourceDir . '/' . $this->mergeNode->filepath))
          $this->sendError(500, "could not merge the image");
      }

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
    $headers[] = "Content-Type: ". $this->node->filetype;
    $headers[] = "Content-Length: ". $this->node->filesize;

    $download = (strstr($this->node->filetype, 'shockwave') === false);

    if ($download)
      $headers[] = "Content-Disposition: attachment; filename=\"{$this->node->filename}\"";

    foreach ($headers as $item)
      header($item);

    $f = fopen($this->source, 'rb')
      or $this->sendError(403, "could not read the file");
    fpassthru($f);
  }
}
