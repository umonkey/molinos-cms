<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?=$title?></title>

    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/style.css" />
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/bebop.css" />

    <script src="<?=$prefix?>/../all/jquery/jquery.js" type="text/javascript" language="javascript"></script>
    <script src="<?=$prefix?>/../all/jquery/plugins/jquery.formtabber.js" type="text/javascript" language="javascript"></script>
    <script src="<?=$prefix?>/js/bebop.js" type="text/javascript" language="javascript"></script>

    <base href="/" />
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
          <h2><?=$title?></h2>
          <?=$form?>
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
      <span>Версия <?=BEBOP_VERSION?></span>
    </div>
    <div class='jqmw hidden' id='defaultPopup'></div>
  </body>
</html>
