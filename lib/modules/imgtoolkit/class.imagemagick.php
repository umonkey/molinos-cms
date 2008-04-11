<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ImageMagick
{
    var $img;
    var $mime;

    private function __construct()
    {
        $this->img = null;
    }

    public function __destruct()
    {
        if ($this->img !== null)
            imagick_free($this->img);
    }

    public function getError()
    {
        return imagick_failedreason($this->img);
    }

    public function getErrorDescription()
    {
        return imagick_faileddescription($this->img);
    }

    public function getType()
    {
        return $this->mime;
    }

    public function open($path, $mimetype = null)
    {
        if ($mimetype === null)
            $this->mime = ImageMagickGD::getFileType($path);
        else
            $this->mime = $mimetype;

        $this->img = imagick_readimage($path);

        if ($this->img) {
            imagick_set_image_quality($this->img, 100);
            return true;
        }

        return false;
    }

    public function save($path)
    {
        $rc = imagick_writeimage($this->img, $path);
        return $rc;
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

    public function scale($width, $height)
    {
        $rc = imagick_scale($this->img, $width, $height);
        return $rc;
    }

    public static function getInstance()
    {
        if (function_exists('imagick_scale'))
            return new ImageMagick();
        else
            return new ImageMagickGD();
    }
};
