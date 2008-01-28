$(document).ready(function () {
  // Открываем нужную вкладку.
  if (window.location.hash) {
    var tab = window.location.hash.substr(1);

    // Скрываем все вкладки.
    $('.tab-active').removeClass('tab-active');
    // Показываем нужную.
    $('#tab-' + tab + '-content').addClass('tab-active');
    // Подсвечиваем её заголовок.
    $('#tab-' + tab).parent().addClass('tab-active');
  }
});

// Отображает нужную вкладку.
function bebop_show_tab(name)
{
  $('.tab-active').removeClass('tab-active');
  $('#tab-' + name + '-content').addClass('tab-active');
  $('#tab-' + name).parent().addClass('tab-active');
}


function bebop_fileapi(fileid, op)
{
  switch (op) {
  case 'open':
    $.ajax({
      type: 'GET',
      url: '/admin/files/?widget=BebopFilesRedux&BebopFilesRedux.classes[]=file&BebopFilesRedux.target='+ fileid,

      success: function (data) {
        $('#defaultPopup').html(data);
        file_open_ready('#defaultPopup', fileid);
        $('#defaultPopup').jqm().jqmShow();
      },

      complete: function () {
        // alert('complete');
      }
    });

    break;

  case 'delete':
    $('#'+ fileid +'-thumbnail img').attr('src', '');
    $('input#'+ fileid +'-id').attr('value', 'delete');
    break;

  case 'replace':
  case 'restore':
  case 'delete':
    var id = '#file-' + fileid +'-upload';

    // Отображаем форму выбора файла.
    $(id + '-form').toggleClass('hidden');
    $(id + '-preview').toggleClass('hidden');

    // Сбрасываем содержимое контролов.
    $('input', id +'-form').attr('value', '');

    // При удалении файла (а не замене) загоняем в скрытый контрол ноль.
    if (op == 'delete')
      $('#ctl-'+ fileid +'-hidden').attr('value', '0');

    break;

  case 'edit':
    var nid = $('img#ctl-'+ fileid +'-thumbnail').attr('src').replace(/^\/attachment\/([0-9]+).*$/, '$1');
    document.location.href = '/admin/node/'+ nid +'/edit/?destination='+ escape(document.location.href);
  }
}

function file_open_ready(e, filename)
{
  $(e + ' .pager a').click(function () {
    $.get($(this).attr('href'), function (data) {
      $(e).html(data);
      file_open_ready(e, filename);
    });

    return false;
  });

  $(e + ' a.jqmClose').click(function () {
    var att = $(this).attr('href');

    $('#'+ filename +'-thumbnail img').attr('src', att + ',200,100,dw');
    $('#'+ filename +'-id').attr('value', att.replace(/^\/attachment\/([0-9]+).*$/, '$1'));
  });

  $(e).jqmAddClose(".jqmClose");
}

function bajcal(widget, mode, div)
{
  var href = window.location.pathname +'?widget='+ widget +'&'+ widget +'.mode='+ mode;

  $.get(href, function (data) {
    $('#'+ div).html(data);
    $('#'+ div).removeClass('hidden');
  });
}
