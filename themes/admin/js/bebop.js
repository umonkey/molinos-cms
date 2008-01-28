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

        $('#widget-BebopContentList').hide();
        $('#form-content-filter-wrapper').show();
      }
    });

    return false;
  });

	$('form.tabbed').formtabber({active: 0});
  $('form textarea.resizable').autogrow();
}
