if (typeof tinyMCE_initializer == 'undefined')
  tinyMCE_initializer = {}

tinyMCE_initializer.mode = "textareas";
tinyMCE_initializer.editor_selector = "visualEditor";
tinyMCE_initializer.theme = "advanced";
tinyMCE_initializer.plugins = "safari,inlinepopups,fullscreen,visualchar";
tinyMCE_initializer.theme_advanced_buttons1 = "bold,italic,underline,strikethrough,separator,bullist,numlist,separator,link,unlink,separator,image,cleanup,separator,charmap,fullscreen";
tinyMCE_initializer.theme_advanced_buttons2 = "";
tinyMCE_initializer.theme_advanced_buttons3 = "";
tinyMCE_initializer.language = "en";
tinyMCE_initializer.file_browser_callback = "mcms_file_pick";

tinyMCE_initializer.theme_advanced_toolbar_location = "top";
tinyMCE_initializer.theme_advanced_statusbar_location = "bottom";

tinyMCE_initializer.theme_advanced_resize_horizontal = true;
tinyMCE_initializer.theme_advanced_resizing = true;

tinyMCE.init(tinyMCE_initializer);

function mcms_file_pick(field_name, url, type, win)
{
  var picker = mcms_path +'admin/content/files?bare=1&picker=' + field_name +'&window='+ (win === undefined ? 'find' : win.name);

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
