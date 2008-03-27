<?php

require_once(dirname(__FILE__) .'/functions.inc');

_admin_check_perm();

$user = mcms::user();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?=$page['title']?> &mdash; Molinos.CMS</title>

    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/bebop.css" />
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/style.css" />
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/notification.css" />
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/topmenu.css" />
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/colors-green.css" />
    
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/../all/jquery/plugins/jquery.suggest.css" />

    <script type="text/javascript" language="javascript" src="<?=$prefix?>/../all/jquery/jquery.js"></script>
    <script type="text/javascript" language="javascript" src="<?=$prefix?>/../all/jquery/plugins/jquery.ifixpng.js" ></script>
    <script type="text/javascript" language="javascript" src="<?=$prefix?>/../all/jquery/plugins/jquery.formtabber.js"></script>
    <script type="text/javascript" language="javascript" src="<?=$prefix?>/../all/jquery/plugins/jquery.autogrow.js" ></script>
    <script type="text/javascript" language="javascript" src="<?=$prefix?>/../all/jquery/plugins/jquery.suggest.js" ></script>
    <script type="text/javascript" language="javascript" src="<?=$prefix?>/js/bebop.js" ></script>
    <base href="<?=$base?>" />
  </head>
  <body>
    <div id="preloaded_images"></div>
      <div id="all">

      <div id="top_menu_controls"><?=$widgets['BebopDashboard']?></div>

      <div id="navbar">
        <div id="top_toolbar">
          <div class="right">
            <div class="greeting">Здравствуйте, <?=_user_name()?></div>
            <a href="/?profile.action=edit&amp;destination=<?=urlencode($_SERVER['REQUEST_URI'])?>">Настройки</a>
            <a id='lnk_exit' href='/admin/?profile.action=logout&amp;destination=%2F'>Выйти</a>
          </div>
        </div>

        <div id="top_menu_controls_bottom"></div>
      </div>

        <div id="content_wrapper">
          <div id="center">
            <?php
              if (null !== ($msg = mcms::message())) {
                print '<div class=\'notification\'>';

                foreach ($msg as $m)
                  print '<p>'. $m .'</p>';

                print '</div>';
              }

              foreach ($widgets as $name => $widget) {
                if ($name != 'BebopDashboard' and $name != 'profile')
                  print "<div id='widget-{$name}'>{$widget}</div>";
              }
          ?></div>

          <div id="left_sidebar">
            <?=_admin_actions()?>
          </div>
        </div>
        <div id='footer_spacer'></div>
      </div>

    <div id="footer">
      <img src="<?=$prefix?>/img/siteimage/logo_molinos_btm_ico.gif" alt="Molinos.Ru" align="middle" />
      <img src="<?=$prefix?>/img/siteimage/logo_molinos_btm.gif" alt="Molinos.Ru" align="middle" />
      <span>Версия <?=$version?></span>
    </div>
    


  </body>
</html>
