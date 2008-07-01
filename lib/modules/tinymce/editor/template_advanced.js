tinyMCE.init({
  mode: "textareas",
  editor_selector:"visualEditor",
  theme: "advanced",
  plugins: "safari,spellchecker,style,layer,table,save,iespell,inlinepopups,media,searchreplace,contextmenu,paste,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,pagebreak",
  theme_advanced_buttons2_add: "cite,|,blockquote,pagebreak",
  theme_advanced_buttons2_add_before: "pastetext,pasteword,separator,search,replace,separator",
  theme_advanced_buttons3_add_before: "tablecontrols,separator",
  theme_advanced_buttons3_add: "iespell,media,separator,fullscreen",
  theme_advanced_toolbar_location: "top",
  theme_advanced_toolbar_align: "left",
  theme_advanced_statusbar_location: "bottom",
  content_css: mcms_path +"lib/modules/tinymce/editor/template_overkill.css",
  plugin_insertdate_dateFormat: "%Y-%m-%d",
  plugin_insertdate_timeFormat: "%H:%M:%S",
  theme_advanced_resize_horizontal: false,
  theme_advanced_resizing: true,
  apply_source_formatting: true,
  spellchecker_languages: "+English=en,Russian=ru,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv",
  relative_urls: false,
  file_browser_callback:"mcms_file_pick"
});

function mcms_file_pick(a, b, c, d)
{
  return mcms_file_pick_real(a, b, c, d, mcms_path);
}
