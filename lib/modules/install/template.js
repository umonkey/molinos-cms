$(document).ready(function () {
  $('#dbtype').change(function () {
    $('fieldset.driversettings').hide();
    $('#' + $(this).attr('value')).show();
  });

  $('#' + $('#dbtype').val()).show();
});
