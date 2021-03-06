if (typeof tinyMCE_initializer == 'undefined')
  tinyMCE_initializer = {}

tinyMCE_initializer.mode = "textareas";
tinyMCE_initializer.editor_selector = "visualEditor";
tinyMCE_initializer.theme = "advanced";
tinyMCE_initializer.plugins = "safari,spellchecker,style,layer,table,save,iespell,inlinepopups,media,searchreplace,contextmenu,paste,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,pagebreak";
tinyMCE_initializer.theme_advanced_buttons2_add = "cite,|,blockquote,pagebreak";
tinyMCE_initializer.theme_advanced_buttons2_add_before = "pastetext,pasteword,separator,search,replace,separator";
tinyMCE_initializer.theme_advanced_buttons3_add_before = "tablecontrols,separator";
tinyMCE_initializer.theme_advanced_buttons3_add = "iespell,media,separator,fullscreen";
tinyMCE_initializer.theme_advanced_toolbar_location = "top";
tinyMCE_initializer.theme_advanced_toolbar_align = "left";
tinyMCE_initializer.theme_advanced_statusbar_location = "bottom";
tinyMCE_initializer.plugin_insertdate_dateFormat = "%Y-%m-%d";
tinyMCE_initializer.plugin_insertdate_timeFormat = "%H:%M:%S";
tinyMCE_initializer.theme_advanced_resize_horizontal = true;
tinyMCE_initializer.theme_advanced_resizing = true;
tinyMCE_initializer.apply_source_formatting = true;
tinyMCE_initializer.spellchecker_languages = "+English=en,Russian=ru,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv";
tinyMCE_initializer.relative_urls = false;
tinyMCE_initializer.file_browser_callback = "mcms_file_pick";
tinyMCE_initializer.language = "ru";

tinyMCE.init(tinyMCE_initializer);
