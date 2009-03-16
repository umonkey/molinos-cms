<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TextDrawer
{
  private $options;
  private $des_img = null;
  private $font = null;

  protected $wrap = 80;

  public $colorR = 0;
  public $colorG = 0;
  public $colorB = 0;

  public $bgColorR = 0xff;
  public $bgColorG = 0xff;
  public $bgColorB = 0xff;

  /**
  * The text size used before it's resized.
  */
  const DRAW_TTF_BASE = 72;

  public function __construct()
  {
    $conf = Context::last()->modconf('drawtext');

    if (isset($conf['font']))
      $this->setFont($conf['font']);
  }

  // Сеттер нужен в любом случае, потому что снаружи
  // приходит id ноды, а не имя файла шрифта.
  private function setFont($font)
  {
    try {
      $fNode = Node::load(array(
        'id' => $font,
        'class' => 'file',
        ));
    } catch (ObjectNotFoundException $e) {
      throw new PageNotFoundException(t('Шрифт с идентификатором %id не найден.', array(
        '%id' => $font,
        )));
    }

    $this->font = mcms::config('filestorage') . DIRECTORY_SEPARATOR . $fNode->filepath;
  }

  /*
  |------------------------------------------------------------
  | This function fixes an issue with imagettftext when 
  | printing some fonts at small point sizes(Like Myriad Pro)
  |
  | Author: Luke Scott
  | Modified by dmkfasi@gmail.com to fit Molinos CMS project
  |------------------------------------------------------------
  */

  /**
  * Draws TTF/OTF text on the destination image with best quality.
  * The built in function imagettftext freaks out with small point 
  * size on some fonts, commonly OTF. Also fixes a position bug
  * with imagettftext using imagettfbbox. If you just want the text
  * pass a null value to 'Destination Image Resource' instead.
  *
  * @param    int            Point Size (GD2), Pixel Size (GD1)
  * @param    int            X Position (Destination)
  * @param    int            Y Position (Destination)
  * @param    string        Text to Print
  * @return    null
  */
  private function drawttftext($size = 14, $posX = 0, $posY = 0, $text = 'Hello, world')
  {
    if ($size > self::DRAW_TTF_BASE)
      throw new RuntimeException('Нельзя задавать размер шрифта больше ' . self::DRAW_TTF_BASE . '.');

    if (empty($text))
      throw new RuntimeException(t('Текст не указан.'));

    if (!is_file($this->font))
      throw new RuntimeException(t('Не удалось загрузить шрифт %name.', array(
        '%name' => $this->font,
        )));

    //-----------------------------------------
    // Simulate text and get data.
    // Get absolute X, Y, Width, and Height
    //-----------------------------------------

    $text_data = imagettfbbox(self::DRAW_TTF_BASE, 0, $this->font, wordwrap($text, $this->wrap, "\n"));
    $posX_font = min($text_data[0], $text_data[6]) * -1;
    $posY_font = min($text_data[5], $text_data[7]) * -1;
    $width = max($text_data[2], $text_data[4]) - min($text_data[0], $text_data[6]);
    $height = max($text_data[1], $text_data[3]) - min($text_data[5], $text_data[7]);

    //-----------------------------------------
    // Calculate ratio and size of sized text
    //-----------------------------------------

    $size_ratio = $size / self::DRAW_TTF_BASE;
    $new_width = round($width * $size_ratio);
    $new_height = round($height * $size_ratio);

    // Если не сделать проверку, то все равно вылетит с Divizion by zero.
    if ($new_width < 1 or $new_height < 1)
      throw new RuntimeException('Не задан размер шрифта или задан слишком малый размер.');

    //-----------------------------------------
    // Create blank translucent image
    //-----------------------------------------

    $im = imagecreatetruecolor($width, $height);
    imagealphablending($im, false);

    // Задаем фон, может быть нужен для того, чтобы
    // буквы выглядели прилично, а не с рваными краями.
    $white = imagecolorallocate($im, $this->bgColorR, $this->bgColorG, $this->bgColorB);
    imagefilledrectangle($im, 0, 0, $width, $height, $white);

    // Задаем цвет прозрачности, который будет удален при
    // переносе изображения текста в копию картинки нужного размера.
    $trans = imagecolorallocatealpha($im, $this->bgColorR, $this->bgColorG, $this->bgColorB, 127);
    imagefilledrectangle($im, 0, 0, $width, $height, $trans);

    //-----------------------------------------
    // Draw text onto the blank image
    //-----------------------------------------

    // Нужно обойти баг, если все цвета нулевые, то ничего не отрисуется.
    if (0 == $this->colorR and 0 == $this->colorG and 0 == $this->colorB)
      $this->colorR = 0x01;

    $m_color = imagecolorallocate($im, $this->colorR, $this->colorG, $this->colorB);
    imagettftext($im, self::DRAW_TTF_BASE, 0, $posX_font, $posY_font, $m_color, $this->font, wordwrap($text, $this->wrap, "\n"));

    //-----------------------------------------
    // Resize text. Can't use resampled direct
    //-----------------------------------------

    $rimg = $this->getBackground($new_width, $new_height);

    $bkg = imagecolorallocate($rimg, $this->bgColorR, $this->bgColorG, $this->bgColorB);
    imagecolortransparent($rimg, $bkg);

    // Надо будет добавить опцию выбора метода переноса текста,
    // потому что разные функции дают разный эффект.
    //imagecopyresized($rimg, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    imagecopyresampled($rimg, $im, $this->options['x'], $this->options['y'], 0, 0, $new_width, $new_height, $width, $height);

    $this->des_img = $rimg;
    imagedestroy($im);
  }

  private function getBackground($w = null, $h = null)
  {
    if (!empty($this->options['background'])) {
      $node = Node::load(array(
        'class' => 'file',
        'id' => $id = $this->options['background'],
        '#error' => t('Не удалось загрузить фоновую картинку с идентификатором %id.', array(
          '%id' => $id,
          )),
        ));

      return $node->getImage();
    }

    $img = imagecreatetruecolor($w * 2, $h * 2);
    $white = imagecolorallocate($img, 0xff, 0, 0xff);
    $bkg = imagecolorallocate($img, $this->bgColorR, $this->bgColorG, $this->bgColorB);
    imagefilledrectangle($img, 0, 0, $w * 2, $h * 2, $bkg);

    return $img;
  }

  private function sendImage()
  {
    // Предполагаем, что у нас всегда PNG, потому что прозрачностей
    // и альфа-каналов в других форматах явно нет.
    header('Content-type: image/png');
    imagepng($this->des_img);
  }

  public function draw(array $options)
  {
    $this->options = $options;

    $text = base64_decode(str_replace(' ', '+', $options['text']));

    if (false === $text or (empty($text) and !empty($options['text'])))
      throw new RuntimeException(t('Не удалось декодировать текст: %base',
        array('%base' => $options['text'])));

    if (isset($options['font']))
      $this->setFont($options['font']);

    if (isset($options['wrap']))
      $this->wrap = $options['wrap'];

    $this->colorR = hexdec(substr($options['color'], 0, 2));
    $this->colorG = hexdec(substr($options['color'], 2, 2));
    $this->colorB = hexdec(substr($options['color'], 4, 2));

    $this->bgColorR = hexdec(substr($options['bgcolor'], 0, 2));
    $this->bgColorG = hexdec(substr($options['bgcolor'], 2, 2));
    $this->bgColorB = hexdec(substr($options['bgcolor'], 4, 2));

    $this->drawttftext($options['size'], $options['padding'], $options['padding'], $text);
    $this->sendImage();
  }
};
