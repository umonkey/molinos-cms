$(function () {
  $('#addfiles .add').click(function () {
    var tpl = $('#cloneme').html();
    for (i=0;i<5;i++) { $('#addfiles ol').append('<li>'+tpl+'</li>'); }
  });

  $('#addfiles.ftp input[name="all"]').change(function () {
    var t = $(this).parents('form:eq(0)').find('table input');
    if ($(this).attr('checked'))
      t.attr('checked', true);
    else
      t.attr('checked', false);
  });
});
