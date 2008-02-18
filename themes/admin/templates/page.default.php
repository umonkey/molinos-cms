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
	
	<link rel="stylesheet" type="text/css" href="<?=$prefix?>/../all/jquery/plugins/jquery.jcarousel.css" />
	<link rel="stylesheet" type="text/css" href="<?=$prefix?>/../all/jquery/plugins/jcarousel-skins/tango/skin.css" />

	<script type="text/javascript" language="javascript" src="<?=$prefix?>/../all/jquery/jquery.js"></script>
	<script type="text/javascript" language="javascript" src="<?=$prefix?>/../all/jquery/plugins/jquery.jcarousel.js" ></script>
	<script type="text/javascript" language="javascript" src="<?=$prefix?>/../all/jquery/plugins/jquery.ifixpng.js" ></script>
	<script type="text/javascript" language="javascript" src="<?=$prefix?>/../all/jquery/plugins/jquery.formtabber.js"></script>
	<script type="text/javascript" language="javascript" src="<?=$prefix?>/../all/jquery/plugins/jquery.autogrow.js" ></script>
	<script type="text/javascript" language="javascript" src="<?=$prefix?>/js/bebop.js" ></script>
	
	    	<script type="text/javascript" src="<?=$prefix?>/tiny/tiny_mce_gzip.js"></script>
	<script type="text/javascript">
		tinyMCE_GZ.init({
			plugins : "table,style,media,inlinepopups,safari,paste,searchreplace,insertdatetime,spellchecker",
			themes : 'advanced',
			skins : 'o2k7',
			languages : 'ru',
			disk_cache : false,
			debug : false
		});
	</script>
	<script type="text/javascript">
		tinyMCE.init({
			mode : "textareas",
			editor_selector : "mceEditor",
			plugins : "table,style,media,inlinepopups,safari,paste,searchreplace,insertdatetime,spellchecker",
			inline_styles : true,
			extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
		 	theme_advanced_path_location : "bottom",
			theme_advanced_resizing : true,
			theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,styleselect,formatselect",
			theme_advanced_buttons2 : "pastetext,pasteword,|,search,replace,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,image,media,|,charmap,insertdate,|,sub,sup,hr",
			theme_advanced_buttons3 : "tablecontrols,|,removeformat,cleanup,visualaid,styleprops,code,|,spellchecker",
			spellchecker_languages : "English=en,+Русский=ru",
			paste_create_paragraphs : false,
			paste_create_linebreaks : false,
			paste_use_dialog : true,
			paste_auto_cleanup_on_paste : true,
			paste_convert_middot_lists : false,
			paste_unindented_list_class : "unindentedList",
			paste_convert_headers_to_strong : true,
			convert_urls : true,
			relative_urls : false,
			file_browser_callback : "mcms_file_pick",
			theme : "advanced",
			language : 'ru',
			skin : 'o2k7'
		});
	</script>
	
    <base href="<?=$base?>" />
  </head>
  <body>
    <div id="preloaded_images"></div>
      <div id="all">

        <div id="top_toolbar">
          <div class="greeting">Здравствуйте, <?=_user_link()?></div>
          <div class="right">
            <?=_admin_check_updates();?>
            <a href="http://code.google.com/p/molinos-cms/issues/list" class="tip">Сообщить о проблеме</a>

          </div>
        </div>

        <?=$widgets['BebopDashboard']?>

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
