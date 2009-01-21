var mcms_picker = {
  mySubmit: function (URL, id) {
    var j = window.opener.jQuery;

    j.ajax({
      type: 'GET',
      url: mcms_path + '/?q=nodeapi.rpc&action=dump&node='+ id,
      dataType: 'json',
      success: function (data) {
        var j = window.opener.jQuery;
        j('#'+ picker +' input.id')
          .attr('value', id);
        j('#'+ picker +' .preview img')
          .attr('src', 'attachment.rpc?fid='+ id + ',48,48,c');
        j('#'+ picker +' .preview a')
          .attr('href', 'attachment.rpc?fid='+ id);
        j('#'+ picker +' .properties .name')
          .attr('value', data.node.name);
        j('#'+ picker +' .properties .name')
          .attr('value', data.node.name);
        j('#'+ picker +' .properties .type')
          .attr('value', data.node.filetype);
        j('#'+ picker +' .properties .dateadded')
          .html(data.node.created);
        j('#'+ picker +' .properties .dateupdated')
          .html(data.node.updated);
        j('#'+ picker +' .filetabs u.tab1').click();
        window.close();
      }
    });

    return false;
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
