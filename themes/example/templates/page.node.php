<?php

mcms::extras('themes/example/styles/lib/refpoint.reset.css');
mcms::extras('themes/example/styles/lib/refpoint.typography-16.css');
mcms::extras('themes/example/styles/lib/refpoint.logo.css');
mcms::extras('themes/example/styles/page.index.css');

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
  <head>
    <title>Просмотр документа — Molinos CMS</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <meta name="robots" content="index,follow" />
    <base href='<?php print $base; ?>' />
		<link rel="shortcut icon" href="themes/all/img/favicon.ico"
      type="image/x-icon" />
    <?php print mcms::extras(); ?>
  </head>
  <body>
    
    <div id="navigation">
      <?php print $widgets['sections']; ?>
    </div>
    
    <div id="content">
      <h1 id="logo"><a href="."><span>Molinos CMS</span></a></h1>
      
      <?php print $widgets['doc']; ?>
    </div>
  </body>
</html>
