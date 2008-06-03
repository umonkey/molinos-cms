<?php

require(realpath(dirname(__FILE__).'/../lib/bootstrap.php'));

$att = new ImageEditor($_GET);
$att->sendFile();

class ImageEditor
{
  var $folder;
  var $filename;
  var $source;
  var $output;
  var $nw;
  var $nh;
  var $options = array(
      'scale' => 100, 
      'mirrorV' => 0, 
      'mirrorH' => 0, 
      'angle' => 0, 
      'offsetX' => 0, 
      'offsetY' => 0,
      'cropX' => 0,
      'cropY' => 0,
      'cropW' => 0,
      'cropH' => 0,
      'merge' => null,
      );

  private $node = null;
  private $mergeNode = null;

  public function __construct($get)
  {
    $args = explode(',', $get['q']);

    $node = Tagger::getInstance()->getObject($args[0]);
    if (empty($node))
      $this->sendError(404, 'attachment not found.');

    $this->node = $node;
    $this->filename = $node['filename'];
    $this->sourceDir = realpath(dirname(dirname(__FILE__)) .'/attachments');
    $this->source = realpath(dirname(dirname(__FILE__)) .'/attachments/'. $node['filepath']);

    // Если кроме id аттачмента ничего не задали, ругаемся
    if (2 > sizeof($args))
        $this->sendError(500, 'usage: '.$get['folder'].'/filename[,scale[,mirrorV[,mirrorH[,offsetX[,offsetY[,cropLeft[,cropTop[,cropRight[,cropBottom]]]]]]]]]');

    $this->folder = realpath(dirname(__FILE__) .'/attachment');
    $this->output = $this->folder . '/' . $get['q'];

    // Сохраняем параметры для обработки изображения
    array_shift($args);
    $i = 0;
    foreach ($this->options as $k => $v) {
      if (!empty($args[$i]))
        $this->options[$k] = floatval($args[$i]);
      $i++;
    }

    if (null != $this->options['merge'])
      $this->mergeNode = Node::load($this->options['merge']);
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
    $rc = $img->open($this->source, $this->node['filetype']);
    
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

      // Если масштабирование не запрошено -- создаём симлинк на картинку,
      // чтобы больше к её обработке не возвращаться (загоняем в кэш).
//      elseif (!symlink($this->source, $this->output))
//          $this->sendError(500, "could not create a caching symlink for this attachment.");
    }

    // Открываем файл на чтение.
    $f = fopen($this->output, 'r')
      or $this->sendError(500, "could not read from cache.");

    // Отправляем файл клиенту.
    header('Content-Type: '.$img->getType());
    header('Content-Length: '.filesize($this->output));
    fpassthru($f);
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
      if (1 == $this->options['mirrorV'])
        $img->mirror('v');

      // Отзеркаливание по горизонтали
      if (1 == $this->options['mirrorH'])
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
      $dX = $this->options['cropX'];
      $dY = $this->options['cropY'];
      $dW = $this->options['cropW'];
      $dH = $this->options['cropH'];

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
