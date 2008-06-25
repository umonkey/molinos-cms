<?php

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?php print $page['title']; ?></title>
    <base href='<?php print $base; ?>' />
    <?php
      mcms::extras('themes/all/css/style.css');
      mcms::extras('themes/all/css/bebop.css');
      print mcms::extras();
    ?>
  </head>
  <body>
    <h1 class='pagetitle'><?php print $page['title']; ?></h1>
    <?php
      if (!empty($page['description']))
        print "<p>". $page['description'] ."</p>";

      if (!empty($widgets)) {
        foreach ($widgets as $k => $v)
          if (!empty($v))
            print mcms::html('div', array(
              'id' => 'widget-'. $k,
              ), $v);
      }
    ?>
  </body>
</html>
