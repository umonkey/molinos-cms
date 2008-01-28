<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <head>
    <title><?=$error['message']?></title>
    <link rel='stylesheet' type='text/css' href='<?=$prefix?>/errors.css' />
  </head>
  <body>
    <h1><a href="http://code.google.com/p/molinos-cms/"><span><?=$error['message']?></span></a></h1>
    <p class='main'><?=$error['description']?></p>
    <?php if (!empty($error['note'])): ?><p><?=$error['note']?></p><?php endif; ?>
  </body>
</html>
