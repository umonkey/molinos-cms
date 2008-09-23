tinyMCE.init({
  mode: "textareas",
  editor_selector:"visualEditor",
  theme: "advanced",
  plugins: "safari,spellchecker,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,pagebreak",
  theme_advanced_buttons1_add_before: "save,newdocument,separator",
  theme_advanced_buttons1_add: "fontselect,fontsizeselect",
  theme_advanced_buttons2_add: "separator,insertdate,inserttime,preview,separator,forecolor,backcolor",
  theme_advanced_buttons2_add_before: "cut,copy,paste,pastetext,pasteword,separator,search,replace,separator",
  theme_advanced_buttons3_add_before: "tablecontrols,separator",
  theme_advanced_buttons3_add: "emotions,iespell,media,advhr,separator,print,separator,ltr,rtl,separator,fullscreen",
  theme_advanced_buttons4: "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,spellchecker,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak,|,insertfile,insertimage",
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
  file_browser_callback:"mcms_file_pick",
  language:"ru"
});

function mcms_file_pick(field_name, url, type, win)
{
  var picker = mcms_path +'/?cgroup=content&mode=list&preset=files&q=admin&picker='+ field_name +'&window='+ (win === undefined ? 'find' : win.name);

  if (type == 'image')
    picker += '&search=image%2F';

  // Параметр не определён только при нажатии в ссылку «подобрать»
  if (win === undefined) {
    window.open(picker);
    return;
  }

  else {
    tinyMCE.activeEditor.windowManager.open({
      file : picker,
      title : 'My File Browser',
      width : 420,  // Your dimensions may differ - toy around with them!
      height : 400,
      resizable : "yes",
      inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
      close_previous : "no"
    }, {
      window : win,
      input : field_name
    });

    return false;
  }
}
