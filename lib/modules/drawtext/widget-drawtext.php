<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

/**
* The text size used before it's resized.
*/

define( 'DRAW_TTF_BASE', 72);

class DrawTextWidget extends Widget
{
  private static $usageMessage = 'Usage: ?font=id&width=X&height=Y&text=...';

  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Растеризатор текста',
      'description' => 'Выдает изображение в формате png с указанными размерами и текстом.',
      );
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);
    
    if (null === ($options['font'] = $ctx->get('font'))) {
        header('Content-Type: text/plain; charset=utf-8');
        die(self::$usageMessage);
    }

    $options['padding'] = $ctx->get('pad', 0);
    $options['color'] = strtolower($ctx->get('color', '0'));
    $options['text'] = $ctx->get('text');
    $options['size'] = $ctx->get('size', DRAW_TTF_BASE);

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    $padding = $options['padding'];
    $text = base64_decode($options['text']);
    $fontId = $options['font'];
    $size = $options['size'];
    $color = $options['color'];

    try {
      $fontObj = Node::load($fontId);
      $fontFile = 'attachments/' . $fontObj->filepath;
    } catch (ObjectNotFoundException $e) {
        header("content-type: text/plain");
        die("Font file {$fontId} not found");
    }

	  $bounds = ImageTTFBBox($size, 0, $fontFile, $text);
		$width = abs($bounds[4] - $bounds[6]);
		$height = abs($bounds[7] - $bounds[1]);

    $img = imagecreatetruecolor($width + ($padding * 2) + 1, $height + ($padding * 2) + 1) or die("Could not create an image {$width}x{$height}.");
    //$img = imagecreatetruecolor($width, $height) or die("Could not create an image {$width}x{$height}.");
    $bg = imagecolorallocatealpha($img, 211, 161, 102, 127) or die("Could not allocate background color.");
    imagefill($img, 0, 0, $bg);

    $cR = hexdec(substr($color, 0, 2));
    $cG = hexdec(substr($color, 2, 2));
    $cB = hexdec(substr($color, 4, 2));
    $this->drawttftext($img, $size, 0, 0, $cR, $cG, $cB, $fontFile, $text);

    imagesavealpha( $img, true );

    header('Content-Type: image/png');
    //header("content-type: text/plain");
    imagepng( $img );
    die();
  }

/*
|------------------------------------------------------------
| This function fixes an issue with imagettftext when 
| printing some fonts at small point sizes(Like Myriad Pro)
|
| Author: Luke Scott
|------------------------------------------------------------
*/

/**
* Draws TTF/OTF text on the destination image with best quality.
* The built in function imagettftext freaks out with small point 
* size on some fonts, commonly OTF. Also fixes a position bug
* with imagettftext using imagettfbbox. If you just want the text
* pass a null value to 'Destination Image Resource' instead.
*
* @param    resource    Destination Image Resource
* @param    int            Point Size (GD2), Pixel Size (GD1)
* @param    int            X Position (Destination)
* @param    int            Y Position (Destination)
* @param    int            Font Color - Red (0-255)
* @param    int            Font Color - Green (0-255)
* @param    int            Font Color - Blue (0-255)
* @param    string        TTF/OTF Path
* @param    string        Text to Print
* @return    null
*/

  private function drawttftext( $des_img, $size, $posX=0, $posY=0, $colorR, $colorG, $colorB, $font='', $text='' )
  {
     //-----------------------------------------
     // Establish a base size to create text
     //-----------------------------------------
     
     if( ! is_int( DRAW_TTF_BASE ) )
     {
         define( 'DRAW_TTF_BASE', 72);
     }
     
     if( $size > DRAW_TTF_BASE )
     {
         define( 'DRAW_TTF_BASE', $size * 2 );
     }
     
     //-----------------------------------------
     // Simulate text and get data.
     // Get absolute X, Y, Width, and Height
     //-----------------------------------------
     
     $text_data = imagettfbbox( DRAW_TTF_BASE, 0, $font, $text );
     $posX_font = min($text_data[0], $text_data[6]) * -1;
     $posY_font = min($text_data[5], $text_data[7]) * -1;
     $height = max($text_data[1], $text_data[3]) - min($text_data[5], $text_data[7]);
     $width = max($text_data[2], $text_data[4]) - min($text_data[0], $text_data[6]);
     
     //-----------------------------------------
     // Create blank translucent image
     //-----------------------------------------
     
     $im = imagecreatetruecolor( $width, $height );
     imagealphablending( $im, false );
     $trans = imagecolorallocatealpha( $im, 0, 0, 0, 127 );
     imagefilledrectangle( $im, 0, 0, $width, $height, $trans );
     imagealphablending( $im, true );
     
     //-----------------------------------------
     // Draw text onto the blank image
     //-----------------------------------------
     
     $m_color = imagecolorallocate( $im, $colorR, $colorG, $colorB );
     imagettftext( $im, DRAW_TTF_BASE, 0, $posX_font, $posY_font, $m_color, $font, $text );
     imagealphablending( $im, false );
     
     //-----------------------------------------
     // Calculate ratio and size of sized text
     //-----------------------------------------
     
     $size_ratio = $size / DRAW_TTF_BASE;
     $new_width = round($width * $size_ratio);
     $new_height = round($height * $size_ratio);
     
     //-----------------------------------------
     // Resize text. Can't use resampled direct
     //-----------------------------------------

     $rimg = imagecreatetruecolor( $new_width, $new_height );
     $bkg = imagecolorallocate($rimg, 0, 0, 0);
     imagecolortransparent($rimg, $bkg);
     imagealphablending($rimg, false);    
     imagecopyresampled($rimg, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

     if( $des_img != NULL )
     {
         //-----------------------------------------
         // Copy resized text to origoinal image
         //-----------------------------------------
         
         imagealphablending($des_img, true);
         imagecopy( $des_img, $rimg, $posX, $posY, 0, 0, $new_width, $new_height );
         imagealphablending($des_img, false);
         imagedestroy( $im );
         imagedestroy( $rimg );
     }
     else
     {
         //-----------------------------------------
         // Just return the resized image
         //-----------------------------------------
         
         $des_img = $rimg;
         imagedestroy( $im );
     }
  }
};
