<?php

mcms::extras('themes/admin/css/bebop.css', false);
mcms::extras('themes/admin/css/style.css', false);
mcms::extras('themes/admin/css/colors-green.css', false);
mcms::extras('themes/all/jquery/jquery.min.js', false);
mcms::extras('themes/all/jquery/plugins/jquery.mcms.tabber.min.js', false);
mcms::extras('themes/admin/js/bebop.min.js', false);
mcms::extras('themes/admin/js/installer.min.js', false);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <base href='<?php print $base; ?>' />
    <?php if (!empty($title)) print mcms::html('title', $title); ?>
    <?php print mcms::extras(); ?>

    <link rel='shortcut icon' href='themes/all/img/lock.png' type='image/png' />
  </head>
  <body>
    <div id="preloaded_images"></div>

    <div id='all'>
      <div id="top_toolbar">
        <div class="greeting">Здравствуйте, <strong>будущий администратор</strong></div>
        <div class="right">
          <a href="http://code.google.com/p/molinos-cms/issues/list" class="tip">Сообщить о проблеме</a>
        </div>
      </div>

      <div id="content_wrapper">
        <div id='center'>
          <h2><?php if (!empty($title)) print $title; ?></h2>
          <?php if (!empty($form)) print $form; ?>
        </div>

        <div id="left_sidebar">
          <div class="menu_block">
            <h4>Ссылки:</h4>
            <ul>
              <li><a href="http://code.google.com/p/molinos-cms/">Сайт Molinos CMS</a></li>
              <li><a href="http://code.google.com/p/molinos-cms/">Документация</a></li>
            </ul>
          </div>
        </div>

      </div>
    </div>

    <div id="footer">
      <img src="<?=$prefix?>/img/siteimage/logo_molinos_btm_ico.gif" alt="Molinos.Ru" align="middle" />
      <img src="<?=$prefix?>/img/siteimage/logo_molinos_btm.gif" alt="Molinos.Ru" align="middle" />
      <span>Версия <?=mcms::version()?></span>
    </div>
    <div class='jqmw hidden' id='defaultPopup'></div>
  </body>
</html>
