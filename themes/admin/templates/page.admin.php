<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Molinos.CMS</title>
    <link rel="stylesheet" type="text/css" href="/themes/admin/css/bebop.css" />
    <link rel="stylesheet" type="text/css" href="/themes/admin/css/style.css" />
    <link rel="stylesheet" type="text/css" href="/themes/admin/css/notification.css" />

    <link rel="stylesheet" type="text/css" href="/themes/all/jquery/plugins/jquery.jcarousel.css" />
    <link rel="stylesheet" type="text/css" href="/themes/all/jquery/plugins/jquery.suggest.css" />
    <link rel="stylesheet" type="text/css" href="/themes/all/jquery/plugins/jcarousel-skins/tango/skin.css" />

    <script type="text/javascript" language="javascript" src="/themes/all/jquery/jquery.js"></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.jcarousel.js" ></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.ifixpng.js" ></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.formtabber.js"></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.autogrow.js" ></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.suggest.js" ></script>
    <script type="text/javascript" language="javascript" src="/themes/admin/js/bebop.js" ></script>
  </head>
  <body>
<?php

if (!empty($dashboard)) {
  print '<div class="dashboard"><h2>Навигация</h2>';
  print '<ul>';

  foreach ($dashboard as $group => $items) {
    print '<ul>';
    print '<li>'. $group;
    print '<ul>';

    foreach ($items as $item)
      print "<li><a href='{$item['href']}'>{$item['title']}</a></li>";

    print '</ul></li></ul>';
  }

  print '</ul></div>';
}

if (!empty($content))
  print $content;

?></body>
</html>
