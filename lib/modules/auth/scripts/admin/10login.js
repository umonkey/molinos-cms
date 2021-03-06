$(function () {
  $('#login-form input[name="auth_type"]').change(function(){
    var mode = $(this).attr('value');
    $(this).parents('form').find('.authmode').hide();
    $('#' + mode).show();
  });
});

$(function(){
  fix_login_form($('form#login input[name="auth_type"]')).change(function(){
    fix_login_form($(this));
  });
});

function fix_login_form(ctl)
{
  var mode = ctl.attr('value');
  ctl.parents('form').find('div').hide();
  $('#' + mode).show().find('input:eq(0)').focus();
  return ctl;
}
