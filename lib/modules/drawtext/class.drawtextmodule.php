<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class DrawTextModule implements iModuleConfig, iRemoteCall
{

  private $des_img = null;
  private $font = null;

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
    $conf = mcms::modconf('drawtext');

    if (isset($conf['font']))
      $this->setFont($conf['font']);
  }

  // Сеттер нужен в любом случае, потому что снаружи
  // приходит id ноды, а не имя файла шрифта.
  public function setFont($font)
  {
    $fNode = Node::load($font);
    $this->font = mcms::config('filestorage') . '/' . $fNode->filepath;
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

    //-----------------------------------------
    // Simulate text and get data.
    // Get absolute X, Y, Width, and Height
    //-----------------------------------------

    $text_data = imagettfbbox(self::DRAW_TTF_BASE, 0, $this->font, $text);
    $posX_font = min($text_data[0], $text_data[6]) * -1;
    $posY_font = min($text_data[5], $text_data[7]) * -1;
    $height = max($text_data[1], $text_data[3]) - min($text_data[5], $text_data[7]);
    $width = max($text_data[2], $text_data[4]) - min($text_data[0], $text_data[6]);

    //-----------------------------------------
    // Create blank translucent image
    //-----------------------------------------

    $im = imagecreatetruecolor($width, $height);
    imagealphablending($im, true);

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
    imagettftext($im, self::DRAW_TTF_BASE, 0, $posX_font, $posY_font, $m_color, $this->font, $text);

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
    // Resize text. Can't use resampled direct
    //-----------------------------------------

    $rimg = imagecreatetruecolor($new_width, $new_height);
    $bkg = imagecolorallocate($rimg, $this->bgColorR, $this->bgColorG, $this->bgColorB);
    imagecolortransparent($rimg, $bkg);

    // Надо будет добавить опцию выбора метода переноса текста,
    // потому что разные функции дают разный эффект.
    //imagecopyresized($rimg, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    imagecopyresampled($rimg, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    $this->des_img = $rimg;
    imagedestroy($im);
 }

  private function sendImage()
  {
    // Предполагаем, что у нас всегда PNG, потому что прозрачностей
    // и альфа-каналов в других форматах явно нет.
    header('Content-type: image/png');
    imagepng($this->des_img);
  }

  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $this->fonts = array();

    foreach (Node::find(array('class' => 'file', 'filetype' => 'application/x-font-ttf')) as $n)
      $this->fonts[$n->id] = isset($n->name) ? $n->name : $n->filename;

    $form->addControl(new EnumControl(array(
      'value' => 'config_font',
      'label' => t('Шрифт по умолчанию'),
      'default' => t('(не использовать)'),
      'options' => $this->fonts,
      'description' => t('Вы можете <a href=\'@url\'>загрузить новый шрифт</a> в файловый архив.', array(
        '@url' => 'adminnode/create/?BebopNode.class=file&destination=CURRENT',
        )),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }

  public static function hookRemoteCall(Context $ctx)
  {
    $options = array();
    $conf = mcms::modconf('drawtext');

    // Перегружаем id шрифта, если пришел параметр извне.
    if (null !== $ctx->get('font'))
      $options['font'] = $ctx->get('font');
    else
      self::usage();

    $options['padding'] = $ctx->get('padding', 0);
    $options['color'] = strtolower($ctx->get('color', '000000'));
    $options['bgcolor'] = strtolower($ctx->get('bgcolor', 'ffffff'));
    $options['text'] = $ctx->get('text', base64_encode('Hello, world!'));
    $options['size'] = $ctx->get('size', DRAW_TTF_BASE);

    self::onGet($options);
    die();
  }

  private static function usage()
  {
    die('See http://code.google.com/p/molinos-cms/wiki/mod_drawtext');
  }

  public function onGet(array $options)
  {
    $text = base64_decode(str_replace(' ', '+', $options['text']));

    if (false === $text or (empty($text) and !empty($options['text'])))
      throw new RuntimeException(t('Не удалось декодировать текст: %base',
        array('%base' => $options['text'])));

    $img = new DrawTextModule();

    if (isset($options['font']))
      $img->setFont($options['font']);

    $img->colorR = hexdec(substr($options['color'], 0, 2));
    $img->colorG = hexdec(substr($options['color'], 2, 2));
    $img->colorB = hexdec(substr($options['color'], 4, 2));

    $img->bgColorR = hexdec(substr($options['bgcolor'], 0, 2));
    $img->bgColorG = hexdec(substr($options['bgcolor'], 2, 2));
    $img->bgColorB = hexdec(substr($options['bgcolor'], 4, 2));

    $img->drawttftext($options['size'], $options['padding'], $options['padding'], $text);
    $img->sendImage();
  }
};
