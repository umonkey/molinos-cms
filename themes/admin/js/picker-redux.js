var mcms_picker = {
  mySubmit: function (URL, id) {
    if (window.mcms_picker_return === undefined) {
      alert('Please load /themes/admin/js/bebop.js');
      return false;
    }
    return mcms_picker_return(URL, id);
  }
};

function mcms_picker_return(href, fileid)
{
  if (mcms_picker_id == 'src' || mcms_picker_id == 'href') {
    var tiny = window.opener.document.getElementById(mcms_gup('window')).document;

    if (tiny)
      $('#'+ mcms_picker_id, tiny).val(href);
    else
      alert('Не удалось достучаться до формы подбора изображения.');
  } else {
    // Заменяем старый предпросмотр новым.
    window.opener.jQuery('#'+ mcms_picker_id +'-preview').remove();
    window.opener.jQuery('#'+ mcms_picker_id +'-input').before("<img id='"+ mcms_picker_id +"-preview' src='"+ mcms_path +"?q=attachment.rpc&fid="+ fileid +",100,100,d' alt='preview' style='margin: 0 4px 4px 0; float: left;' />");

    // Заменяем скрытое значение.
    window.opener.jQuery('#'+ mcms_picker_id +'-hidden').attr('value', fileid);

    // Сбрасываем отметку об удалении.
    window.opener.jQuery('#center #'+ mcms_picker_id +'-del-link').removeClass('bold');
  }

  window.close();
  return false;
}
