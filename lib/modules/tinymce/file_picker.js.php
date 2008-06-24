<?php

require_once(dirname(dirname(dirname(__FILE__))) .'/bootstrap.php');

header('Content-Type: text/javascript; charset=utf-8');

?>function mcms_picker_return(href)
{
  var fileid = href.replace('attachment/', '');

  if (mcms_picker_id == 'src') {
    var wod = window.opener.document;
    var em = wod.getElementById(mcms_picker_id);

    if (em) {
      em.value = href;
    } else if (em = wod.getElementById('mce_0_ifr')) {
      var src = jQuery(em.contentDocument).find('#'+ mcms_picker_id);
      if (src.length == 0) {
        alert('Error accessing the parent dialog.');
        return false;
      }

      src.val(href);
      window.close();

      return false;
    } else {
      alert('Target control not found');
      return false;
    }
  } else {
    // Заменяем старый предпросмотр новым.
    window.opener.jQuery('#'+ mcms_picker_id +'-preview').remove();
    window.opener.jQuery('#'+ mcms_picker_id +'-input').before("<img id='"+ mcms_picker_id +"-preview' src='<?php print l('attachment/'); ?>"+ fileid +",100,100,d' alt='preview' style='margin: 0 4px 4px 0; float: left;' />");

    // Заменяем скрытое значение.
    window.opener.jQuery('#'+ mcms_picker_id +'-hidden').attr('value', fileid);

    // Сбрасываем отметку об удалении.
    window.opener.jQuery('#center #'+ mcms_picker_id +'-delete').attr('checked', '');
  }

  window.close();
  return false;
}
