<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

/**
* The text size used before it's resized.
*/

define( 'DRAW_TTF_BASE', 72);

class DrawTextModule implements iModuleConfig, iRemoteCall
{
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

  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $fonts = array();

    foreach (Node::find(array('class' => 'file', 'filetype' => 'application/x-font-ttf')) as $n)
      $fonts[$n->id] = isset($n->name) ? $n->name : $n->filename;

    $form->addControl(new EnumControl(array(
      'value' => 'config_font',
      'label' => t('Шрифт по умолчанию'),
      'default' => t('(не использовать)'),
      'options' => $fonts,
      'description' => t('Вы можете <a href=\'@url\'>загрузить новый шрифт</a> в файловый архив.', array(
        '@url' => 'adminnode/create/?BebopNode.class=file&destination=CURRENT',
        )),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }

  public static function hookRemoteCall(RequestContext $ctx)
  {
    $options = array();
    $conf = mcms::modconf('drawtext');

    if (null !== $ctx->get('font'))
      $options['font'] = $ctx->get('font');
    elseif (isset($conf['font']))
      $options['font'] = $conf['font'];
    else
      self::usage();

    $options['padding'] = $ctx->get('padding', 0);
    $options['color'] = strtolower($ctx->get('color', '000000'));
    $options['text'] = $ctx->get('text', base64_encode('Hello, world!'));
    $options['size'] = $ctx->get('size', DRAW_TTF_BASE);

    self::onGet($options);
    die();
  }

  private static function usage()
  {
    die('See http://code.google.com/p/molinos-cms/wiki/mod_drawtext');
  }
};
