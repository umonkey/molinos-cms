$(document).ready(function () {
  $('#dbtype').change(function () {
    $('fieldset.driversettings').hide();
    $('#' + $(this).attr('value')).show();
  });

  $('#' + $('#dbtype').val()).show();

  $('#fs-settings legend').click(function(){
    $(this).parent().find('div').show();
  });
});
