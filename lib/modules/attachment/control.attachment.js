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
    url = mcms_path + '/?q=admin&mode=list&preset=files&cgroup=content&mcmsarchive=1&picker='+ field_name;
    window.open(url, '_blank');
  }
};

try {
  $(document).ready(function () {
    $('table.files a.pickerlite').click(function () {
      var p = $(this).parents('tr').eq(0).attr('id');
      var url = mcms_path + '/?q=admin&mode=list&preset=files'
        +'&cgroup=content&mcmsarchive=1&picker='+ p;
      window.open(url);
      return false;
    });

    // Переключение локального/удалённого файла при добавлении в архив.
    $('form.node-file-create-form .form-radio').change(function () {
      var mode = $(this).attr('value');

      $('form.node-file-create-form .attachment-wrapper')
        .css('display', (mode == 'local') ? 'block' : 'none');
      $('form.node-file-create-form .url-wrapper')
        .css('display', (mode == 'remote') ? 'block' : 'none');
    });

    // Смена режима загрузки файла.
    $('.attachment-control .controls span').click(function () {
      var p = $(this).parent().parent();

      $('.tab', p).hide();
      $('.controls span', p).removeClass('active');

      if ($(this).attr('class') == 'ftp') {
        $.get(mcms_path +'?q=attachment.rpc', {
          action: 'ftp',
          name: $(':file', p).attr('name'),
          single: p.hasClass('single') ? 1 : 0,
        }, function (response) {
          $('.ftp-results', p).html(response);
        });
      }

      $('.tab.'+ $(this).attr('class')).show();
      $(this).addClass('active');
    });

    // Поиск по файловому архиву.
    $('.attachment-control .text-search').keyup(function () {
      var p = $(this).parent().parent();

      $.get(mcms_path +'?q=attachment.rpc', {
        action: 'find',
        search: $(this).attr('value'),
        name: $(':file', p.parent()).attr('name')
        }, function (response) {
          $('.search-result', p).html(response);
        });
    });
  });
} catch (e) {
  // Исключение нужно обрабатывать потому, что этот же скрипт
  // грузится на странице выбора файла, но jQuery там нет (и не нужен).
}
