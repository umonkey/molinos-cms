<?php

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?=$page['title']?></title>
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/style.css" />
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/bebop.css" />
    <base href='<?=$page['base']?>' />
  </head>
  <body>
    <h1><?=$page['title']?></h1>
    <?php if (!empty($page['description'])) print "<p>". $page['description'] ."</p>"; ?>

    <?php
      if (!empty($widgets)) {
        foreach ($widgets as $k => $v)
          if (!empty($v))
            print "<div id='widget-{$k}'>{$v}</div>";
      }
    ?>
  </body>
</html>
