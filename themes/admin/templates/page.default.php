<?php

require_once(dirname(__FILE__) .'/functions.inc');

$user = AuthCore::getInstance()->getUser();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?=$page['title']?> &mdash; Molinos.CMS</title>
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/style.css" />
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/bebop.css" />
    <script src="<?=$prefix?>/../all/jquery/jquery.js" type="text/javascript" language="javascript"></script>
    <script src="<?=$prefix?>/../all/jquery/plugins/jquery.formtabber.js" type="text/javascript" language="javascript"></script>
    <script src="<?=$prefix?>/../all/jquery/plugins/jquery.autogrow.js" type="text/javascript" language="javascript"></script>
    <script src="<?=$prefix?>/js/bebop.js" type="text/javascript" language="javascript"></script>

    <script language="javascript" type="text/javascript" src="<?=$prefix?>/tiny_mce/tiny_mce_gzip.php"></script>
    <script language="javascript" type="text/javascript" src="<?=$prefix?>/tiny_mce/config.js"></script>

    <base href="<?=$base?>" />
  </head>
  <body>
    <div id="preloaded_images"></div>
      <div id="all">

        <div id="top_toolbar">
          <div class="greeting">Здравствуйте, <a href='/admin/node/<?=$user->getUid()?>/edit/?destination=<?=urlencode($_SERVER['REQUEST_URI'])?>'><strong><?=$user->getName()?></strong></a></div>
          <div class="right">
            <?=_admin_check_updates();?>
            <a href="http://code.google.com/p/molinos-cms/issues/list" class="tip">Сообщить о проблеме</a>
            <div id="user-profile"><?=$widgets['profile']?></div>
          </div>
        </div>

        <?=$widgets['BebopDashboard']?>

        <div id="content_wrapper">
          <div id="center">
            <?php foreach ($widgets as $name => $widget) {
              if ($name != 'BebopDashboard' and $name != 'profile')
                print "<div id='widget-{$name}'>{$widget}</div>";
            } ?>
          </div>

          <div id="left_sidebar">
            <?=_admin_actions()?>
            <div class="menu_block">
              <h4>Ярлыки:</h4>
              <ul><?php if ($_SERVER['HTTP_HOST'] == 'www.bspb.ru'): ?>
                  <li><a href="/admin/content/?BebopContentList.classes%5B%5D=orderCard">Заказы пластиковых карт</a></li>
                  <li><a href="http://www.bspb.ru/awstats/awstats.pl" target="_blank">Статистика посещения</a></li>
                  <?php endif; ?>
                <li><a href="/admin/logs/?BebopLogs.op=search">Поисковые запросы</a></li>
                <li><a href="<?php
                  $url = bebop_split_url();
                  $url['args']['xlush'] = 1;
                  print str_replace('xlush=1', 'flush=1', bebop_combine_url($url));
                ?>">Очистить кэш</a></li>
              </ul>
              <ul class="add_remove">
                <li><a href="#" class="tip">добавить/удалить</a></li>
              </ul>
            </div>
          </div>
        </div>
        <div id='footer_spacer'></div>
      </div>
    </div>

    <div id="footer">
      <img src="<?=$prefix?>/img/siteimage/logo_molinos_btm_ico.gif" alt="Molinos.Ru" align="middle" />
      <img src="<?=$prefix?>/img/siteimage/logo_molinos_btm.gif" alt="Molinos.Ru" align="middle" />
      <span>Версия <?=$version?></span>
    </div>
    <div class='jqmw hidden' id='defaultPopup'></div>
  </body>
</html>
