<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ImageMagickGD
{
    var $img;
    var $mime;
    var $error;
    var $errorlong;
    var $quality;

    public function __construct()
    {
        $this->img = null;
    }

    public function __destruct()
    {
        if ($this->img !== null)
            imagedestroy($this->img);
    }

    public function getError()
    {
        return $this->error;
    }

    public function getErrorDescription()
    {
        return $this->errorlong;
    }

    public function getType()
    {
        return $this->mime;
    }

    public static function getFileType($path)
    {
      return bebop_get_file_type($path);
    }

    public function open($path, $mimetype = null)
    {
        if ($mimetype === null)
          $this->mime = self::getFileType($path);
        else
          $this->mime = $mimetype;

        switch ($this->mime) {
            case 'image/jpeg':
            case 'image/pjpeg':
                $this->img = imagecreatefromjpeg($path);
                break;
            case 'image/png':
            case 'image/x-png':
                $this->img = imagecreatefrompng($path);
                break;
            case 'image/gif':
                $this->img = imagecreatefromgif($path);
                break;
            default:
                $this->error = "bad file";
                $this->errorlong = "unknown image file format";
                return false;
        }

        return ($this->img !== FALSE);
    }

    public function save($path)
    {
        switch ($this->mime) {
            case 'image/jpeg':
            case 'image/pjpeg':
                $rc = imagejpeg($this->img, $path, $this->quality);
                break;
            case 'image/png':
            case 'image/x-png':
                $rc = imagepng($this->img, $path);
                break;
            case 'image/gif':
                $rc = imagegif($this->img, $path);
                break;
        }

        return ($rc === TRUE);
    }

    public function dump()
    {
      $result = array(
        'type' => $this->mime,
        );

      if (false === ($tmp = tempnam(mcms::config('tmpdir'), 'gd')))
        throw new RuntimeException(t('Could not scale the image.'));

      if (!$this->save($tmp))
        throw new RuntimeException(t('Could not scale the image.'));

      $result['data'] = file_get_contents($tmp);

      unlink($tmp);

      return $result;
    }

    private function getScale($nw, $nh)
    {
        $w = imagesx($this->img);
        $h = imagesy($this->img);

        if (empty($nh)) {
            $scale = $w / $nw;
            $nh = $h / $scale;
        } elseif (empty($nw)) {
            $scale = $h / $nh;
            $nw = $w / $scale;
        } else {
            $scale = $w / $nw;

            if ($h / $scale > $nh)
                $scale = $h / $nh;

            $nw = $w / $scale;
            $nh = $h / $scale;
        }

        return array($nw, $nh);
    }

    private function getScaleInfo($rw, $rh, array $options)
    {
      // Параметры трансформации.  Префиксы: s=src, d=dst, o=orig, r=request.
      $map = array(
        'sx' => 0,
        'sy' => 0,
        'sw' => imagesx($this->img),
        'sh' => imagesy($this->img),
        'dx' => 0,
        'dy' => 0,
        'dw' => 0,
        'dh' => 0,
        'ow' => imagesx($this->img),
        'oh' => imagesy($this->img),
        'rw' => intval($rw), // запрошенная ширина, может меняться при !crop
        'rh' => intval($rh), // запрошенная высота, может меняться при !crop
        );

      // Проверим, всё ли в порядке.
      if ($map['rw'] == 0 and $map['rh'] == 0)
        throw new Exception("zero image size at both dimension");

      // Дополним отсутствующие координаты.
      if (empty($map['rw']))
        $map['rw'] = $map['ow'] * ($map['rh'] / $map['oh']);
      elseif (empty($map['rh']))
        $map['rh'] = $map['oh'] * ($map['rw'] / $map['ow']);

      // Обрезание: размер картинки задан изначально, подгоняем.
      if ($options['crop']) {
        if ($options['downsize'])
          $scale = min($map['rw'] / $map['ow'], $map['rh'] / $map['oh'], 1);
        else
          $scale = max($map['rw'] / $map['ow'], $map['rh'] / $map['oh']);
      }

      // Без обрезания: просто масштабирование.
      else {
        if ($options['downsize'])
          $scale = min($map['rw'] / $map['ow'], $map['rh'] / $map['oh'], 1);
        else
          $scale = min($map['rw'] / $map['ow'], $map['rh'] / $map['oh']);

        $map['rw'] = intval($map['ow'] * $scale);
        $map['rh'] = intval($map['oh'] * $scale);
      }

      // Теперь, зная точный коэффициент, масштабируем картинку.
      $map['dw'] = intval($map['sw'] * $scale);
      $map['dh'] = intval($map['sh'] * $scale);
      $map['dx'] = intval(($map['rw'] - $map['dw']) / 2);
      $map['dy'] = intval(($map['rh'] - $map['dh']) / 2);

      return $map;
    }

    public function scale($width, $height, array $_options = null)
    {
        // Нормализация опций, во избежание ворнингов.
        $options = array_merge(array('downsize' => false, 'crop' => false, 'white' => false, 'quality' => 85), $_options == null ? array() : $_options);

        // Сохраним качество, понадобится в будущем.
        $this->quality = $options['quality'];

        // Получаем информацию для масштабирования.
        $map = $this->getScaleInfo($width, $height, $options);

        if (true or imageistruecolor($this->img))
            $dst = imagecreatetruecolor($map['rw'], $map['rh']);
        else
            $dst = imagecreate($map['dw'], $map['dh']);

        if ($dst === FALSE) {
            $this->error = "imagecreate failed";
            $this->errorlong = "could not create a new {$map['dw']}x{$map['dh']} image";
            return false;
        }

        // Заполняем белым цветом, если надо.
        if (!empty($options['white'])) {
          $color = imagecolorallocate($dst, 255, 255, 255);
          imagefilledrectangle($dst, 0, 0, $map['rw'], $map['rh'], $color);
        }
        // Если не белый, значит прозрачный.
        else
        {
          imagealphablending($dst, false);
          $color = imagecolorallocatealpha($dst, 0, 0, 0, 127);

          // Нормальной прозрачности для масштабированных гифов получить
          // не удалось, принудительно сохраняем в PNG.
          $this->mime = 'image/png';

          imagefilledrectangle($dst, 0, 0, $map['rw'], $map['rh'], $color);
          imagesavealpha($dst, true);
          imagealphablending($dst, true);
        }

        if (!imagecopyresampled($dst, $this->img, $map['dx'], $map['dy'], $map['sx'], $map['sy'], $map['dw'], $map['dh'], $map['sw'], $map['sh'])) {
            $this->error = "resizing failed";
            $this->errorlong = "could not resize te image to {$map['dw']}x{$map['dh']}";
            return false;
        }

        imagedestroy($this->img);
        $this->img = $dst;
        return true;
    }
};
