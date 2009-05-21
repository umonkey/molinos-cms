$(function () {
  $('fieldset.tab:gt(0) > div').hide();
  $('fieldset.tab > legend').click(function(){
    var tab = $(this).parents('fieldset');
    var frm = $(this).parents('form:eq(0)');
    var count = $('>.control:visible', tab).length;
    $('fieldset.tab > .controls', frm).hide();
    if (!count)
      $('> .controls', tab).show();
  });
});
