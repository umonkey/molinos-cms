$(function(){
  // $('.picker .field-name a').click(function () {
  $('.picker .field-name a').click(function () {
    try {
      var picker = $('#pickerId').val();
      if (undefined === picker) {
        alert('Отсутствует идентификатор получателя.');
        return false;
      }

      var win = tinyMCEPopup.getWindowArg("window");
      win.document.getElementById(
        tinyMCEPopup.getWindowArg("input")).value = $(this).attr('href');

      if (win.ImageDialog) {
        // for image browsers: update image dimensions
        if (win.ImageDialog.getImageData)
          win.ImageDialog.getImageData();
        if (win.ImageDialog.showPreviewImage)
          win.ImageDialog.showPreviewImage(URL);
      }

      tinyMCEPopup.close();
    } catch (e) {
      alert(e);
    }
    return false;
  });
});

function mcms_file_pick(field_name, url, type, win)
{
  var picker = mcms_path +'admin/content/files?bare=1&tinymcepicker=' + field_name +'&window='+ (win === undefined ? 'find' : win.name);

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
      title : 'Файловый архив Molinos CMS',
      width : 720,
      height : 580,
      resizable : "yes",
      inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
      close_previous : "no",
      popup_css : false
    }, {
      window : win,
      input : field_name
    });

    return false;
  }
}

function mcms_tinymce_pick(fileid,picker)
{
  try {
    var wod = window.opener.document;
    var em = wod.getElementById(picker);

    if (em) {
      em.value = href;
    } else if (em = wod.getElementById('mce_0_ifr')) {
      var src = jQuery(em.contentDocument).find('#'+ picker);
      if (src.length == 0) {
        alert('Error accessing the parent dialog.');
      } else {
        src.val(href);
      }
    } else {
      alert('Target control not found');
    }
  } catch (e) {
    alert(e);
  }

  window.close();
}
