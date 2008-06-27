// Подбор файлов для Molinos.CMS.  Используется при редактировании
// объектов, для выбора существующего файла их архива (открывает
// всплывающее окно).

var mcms_picker = {
  mySubmit: function (URL, id) {
    try {
      var win = tinyMCEPopup.getWindowArg("window");
      win.document.getElementById(
        tinyMCEPopup.getWindowArg("input")).value = URL;

      if (win.ImageDialog) {
        // for image browsers: update image dimensions
        if (win.ImageDialog.getImageData)
          win.ImageDialog.getImageData();
        if (win.ImageDialog.showPreviewImage)
          win.ImageDialog.showPreviewImage(URL);
      }

      tinyMCEPopup.close();
    } catch (e) {
      alert('Не удалось передать файл в редактор: '+ e.message);
    }
  },

  open: function (field_name) {
    url = mcms_path + '/admin?mode=list&preset=files&cgroup=content&mcmsarchive=1&picker='+ field_name;
    window.open(url, '_blank');
  }
};

try {
  $(document).ready(function () {
    $('#center .form-file.archive').each(function (i) {
      var f = $('#center .form-file.archive').eq(i);
      var id = f.attr('id').replace('-input', '');

      var html = ""
        +"<label style='display:inline'><input type='checkbox' value='1' id='"+id+"-delete' name='"+$('#'+id+'-input').attr('name')+"[delete]' /> удалить</label>"
        +" или <a href='javascript:mcms_picker.open(\""+ id +"\");'>подобрать</a>"
        ;

      f.after("<p class='attctl'>"+ html +'</p>');

      var current = $('#center :hidden#'+ id +'-hidden').attr('value');
      if (current)
        f.before("<img id='"+ id +"-preview' src='/attachment/"+ current +",100,100,d' alt='preview' style='margin: 0 4px 4px 0; float: left;' />");

      $('#center #'+ id +'-input').parent().after("<div style='clear: both;'></div>");
    });
  });
} catch (e) {
  // Исключение нужно обрабатывать потому, что этот же скрипт
  // грузится на странице выбора файла, но jQuery там нет (и не нужен).
}
