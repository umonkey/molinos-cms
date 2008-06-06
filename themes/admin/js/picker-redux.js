var mcms_picker = {
  mySubmit: function (URL) {
    if (window.mcms_picker_return === undefined) {
      alert('Please load /themes/admin/js/bebop.js');
      return false;
    }
    return mcms_picker_return(URL);
  }
};

function mcms_picker_return(href)
{
  var fileid = href.replace('/attachment/', '');

  if (mcms_picker_id == 'src') {
    if (tinyMCE) {
      var tmp_win = tinyMCE.getWindowArg('window');
      if (tmp_win) {
        tmp_win.document.getElementById('src').value = href;
      }
    }
  } else {
    // Заменяем старый предпросмотр новым.
    window.opener.jQuery('#'+ mcms_picker_id +'-preview').remove();
    window.opener.jQuery('#'+ mcms_picker_id +'-input').before("<img id='"+ mcms_picker_id +"-preview' src='/attachment/"+ fileid +",100,100,d' alt='preview' style='margin: 0 4px 4px 0; float: left;' />");

    // Заменяем скрытое значение.
    window.opener.jQuery('#'+ mcms_picker_id +'-hidden').attr('value', fileid);

    // Сбрасываем отметку об удалении.
    window.opener.jQuery('#center #'+ mcms_picker_id +'-del-link').removeClass('bold');
  }

  window.close();
  return false;
}
