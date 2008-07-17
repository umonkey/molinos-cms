<?php

mcms::extras('themes/all/styles/lib/refpoint.reset.css');
mcms::extras('themes/all/styles/lib/refpoint.typography-16.css');
mcms::extras('themes/all/styles/pages.error.css');

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
  <head>
    <title><?php print $error['message']; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="robots" content="none" />
    <?php empty($base) or print mcms::html('base', array('href' => $base)); ?>
    <link rel="shortcut icon"
      href="themes/all/img/favicon.ico"
      type="image/x-icon" />
    <?php print mcms::extras(); ?>
  </head>
  <body>
    
    <div id="content">
      <h1 id="logo"><a href="."><span>Molinos CMS</span></a></h1>
    
      <h2><?php print $error['message']; ?></h2>
      <?php if (!empty($error['description'])): ?>
        <p><?php print $error['description']; ?></p>
      <?php endif; ?>
    
    </div>
    
  </body>
  <!-- request time: $execution_time ms. -->
</html>

