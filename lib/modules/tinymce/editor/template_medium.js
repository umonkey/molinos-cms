tinyMCE.init({
  mode:"textareas",
  editor_selector:"visualEditor",
  theme:"advanced",
  plugins:"safari,inlinepopups,fullscreen,visualchar",
  theme_advanced_buttons1:"bold,italic,underline,strikethrough,separator,bullist,numlist,separator,link,unlink,separator,image,cleanup,separator,charmap,fullscreen",
  theme_advanced_buttons2:"",
  theme_advanced_buttons3:"",
  language:"en",
  file_browser_callback:"mcms_file_pick"
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
