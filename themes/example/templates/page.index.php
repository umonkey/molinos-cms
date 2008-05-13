<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Molinos.CMS Demo</title>
    <link rel='stylesheet' type='text/css' href='<?php print $prefix; ?>/styles/page.index.css' />
  </head>
  <body>
    <div id='wrapper'>
      <?php
        foreach (array('title', 'menu', 'doclist', 'doc') as $w)
          if (!empty($widgets[$w]))
            print mcms::html('div', array('id' => 'widget-'. $w), $widgets[$w]);
      ?>
    </div>
  </body>
</html>
