// Подключаем события через функцию, т.к. нам понадобится
// переподключать события при использовании аяксовых форм.
$(document).ready(bebop_init);

// Подключение обработчиков событий.
function bebop_init()
{
  var win = window.opener ? window.opener : window.dialogArguments, c;
  if (win) {
    tinyMCE = win.tinyMCE;
  }

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

  $('.returnHref a').click(function () {
    if (tinyMCE) {
      var tmp_win = tinyMCE.getWindowArg('window');
      if (tmp_win) {
        tmp_win.document.getElementById('href').value = $(this).attr('href');
      }
      window.close();
      return false;
    }
  });

  $('a.returnHref').click(function () {
    return mcms_picker_return($(this).attr('href'));
  });

  // Обрабатываем сохранение документов без перегрузки страницы.
  $('#node-edit-form #ctl-save-draft').click(function () {
    var form = $('#node-edit-form');

    // Форсируем сохранение содержимого TinyMCE.
    if (tinyMCE) {
      for (n in tinyMCE.instances) {
        inst = tinyMCE.instances[n];
        if (tinyMCE.isInstance(inst)) {
          tinyMCE.execCommand('mceToggleEditor', false, inst.editorId);
          tinyMCE.execCommand('mceToggleEditor', false, inst.editorId);
        }
      }
    }

    // Отключаем кнопку сохранения.
    $(this).attr('disabled', '1');
    // $(this).prepend("<img id='busy-indicator' src='/themes/all/img/wait.gif' witdh='16' height='16' alt='wait' />");

    $.ajax({
      type: "POST",
      url: form.attr('action'),
      data: form.serialize() + '&widget=BebopNode',
      dataType: "json",
      success: function (data) {
        if (data.rid) {
          // Удаляем старый номер ревизии, если есть.
          $('input[name="BebopNode.rev"]', form).remove();

          // Добавляем новый.
          form.append("<input type='hidden' name='BebopNode.rev' value='" + data.rid + "' />");
        }

        else if (data.message) {
          alert(data.message);
        }
      },
      error: function (data) {
        alert('Error: '+ data.message);
      },
      complete: function (xml, status) {
        // Снова активируем кнопку сохранения.
        // $('#busy-indicator').remove();
        $('#ctl-save-draft', form).attr('disabled', '');
      }
    });

    return false;
  });

  $('#ctl-preview').click(function () {
    var form = $('#node-edit-form');

    $.ajax({
      type: "POST",
      url: form.attr('action').replace('/edit/', '/preview/'),
      data: form.serialize(),
      dataType: "json",
      success: function (data) {
        if (data.message)
          alert(data.message);

        if (data.preview) {
          $('#node-preview').remove();
          $('#node-edit-form').after("<div style='padding: 4px; border: solid black 1px; margin: 4px 0; clear: both' id='node-preview'>" + data.preview + "</div>");
        }
      },
      error: function (xml, status, error) {
        alert('Error.');
      }
    });

    return false;
  });

  // Открываем некоторые ссылки в диалогах.
  $('a.popupWrapper').click(bebop_popup_wrapper);

  // Изменение порядка элементо, подавляем перезагрузку всей страницы.
  $('a.reordercmd').click(function () {
    var cl = $('#widget-BebopContentList');
    cl.addClass('grayout');

    $.ajax({
      'type': 'GET',
      'url': $(this).attr('href'),
      'dataType': 'json',

      success: function (data) {
        if (data.status != 0) {
          var url = window.location.href;

          if (url.indexOf('?') == -1)
            url += '?';
          else
            url += '&';

          url += 'widget=BebopContentList';

          $.get(url, function (data) {
            cl.html(data);
            cl.removeClass('grayout');
            bebop_init();
            });
        } else {
          cl.removeClass('grayout');
        }
      },

      error: function () {
        cl.removeClass('grayout');
      }
      });

    return false;
  });

  $('.control-FieldControl-wrapper .selector').click(function () {
    var field = $(this).attr('href').replace(/.*#/, '');

    $('.control-FieldControl-wrapper table').addClass('hidden');
    $('#field-'+ field +'-editor table').removeClass('hidden');

    return false;
  });

  $('#user-logout-form').submit(function () {
    $.ajax({
      type: "POST",
      url: $(this).attr('action'),
      data: $(this).serialize(),
      dataType: "json",

      success: function (data) {
        if (data.status = 'ok') {
          $('#center').html(data.message);
          $('#top_menu').hide('slow');
          $('#left_sidebar').hide('slow');
          $('#user-profile').hide('slow');
          $('#top_toolbar .greeting').hide('slow');
        }
      }
    });

    return false;
  });

  $('#center .ctrl_filter').click(function () {
    $.ajax({
      type: "GET",
      url: $(this).attr('href') + '?widget=BebopContentFilterSettings',

      success: function (data) {
        $('#widget-BebopContentList').after(data);
        $('#form-content-filter-wrapper').hide();

        $('#widget-BebopContentList').hide('slow');
        $('#form-content-filter-wrapper').show('slow');
      }
    });

    return false;
  });

  $('#center .manualcron').click(function () {
    $.ajax({
      url: $(this).attr('href'),
      mode: 'json',
      success: function () {
        window.location.href = '/admin/?cron=ok';
      }
    });

    return false;
  });
}


function bebop_popup_wrapper()
{
  var widget = '';
  var id = $(this).attr('id');

  // Определяем виджет, который мы будем вызывать.
  if (id && id.substring(0, 5) == 'popup') {
    widget = id.substring(5, id.length);
  } else if ($(this).attr('href').match('/schema/field/')) {
    widget = 'BebopSchemaField';
  } else {
    widget = 'BebopNode';
  }

  var url = $(this).attr('href');
  if (url.indexOf('?') == -1)
    url += '?widget='+ widget;
  else
    url += '&widget='+ widget;

  $.get(url, function (data) {
    $('#defaultPopup').html(data);
    $('#defaultPopup').jqm().jqmShow();
  });
  return false;
}
