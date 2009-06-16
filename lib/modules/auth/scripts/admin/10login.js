$(function () {
  $('#login-form input[name="auth_type"]').change(function(){
    var mode = $(this).attr('value');
    $(this).parents('form').find('.authmode').hide();
    $('#' + mode).show();
  });
});
