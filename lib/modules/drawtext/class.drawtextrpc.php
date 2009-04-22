<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class DrawTextRPC
{
  public static function hookRemoteCall(Context $ctx)
  {
    $options = array();
    $conf = $ctx->modconf('drawtext');

    // Перегружаем id шрифта, если пришел параметр извне.
    if (null !== $ctx->get('font'))
      $options['font'] = $ctx->get('font');
    else
      self::usage();

    $options['background'] = $ctx->get('background');
    $options['padding'] = $ctx->get('padding', 0);
    $options['color'] = strtolower($ctx->get('color', '000000'));
    $options['bgcolor'] = strtolower($ctx->get('bgcolor', 'ffffff'));
    $options['text'] = $ctx->get('text', base64_encode('Hello, world!'));
    $options['size'] = $ctx->get('size', TextDrawer::DRAW_TTF_BASE);
    $options['x'] = $ctx->get('x', 0);
    $options['y'] = $ctx->get('y', 0);
    $options['wrap'] = $ctx->get('wrap');

    $drawer = new TextDrawer();
    $drawer->draw($options);

    exit();
  }

  private static function usage()
  {
    die('See http://code.google.com/p/molinos-cms/wiki/mod_drawtext');
  }
};
