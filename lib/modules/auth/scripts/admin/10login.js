$(function(){
  fix_login_form($('#login-form input[name="auth_type"]')).change(function(){
    fix_login_form($(this));
  });
});

function fix_login_form(ctl)
{
  var mode = ctl.attr('value');
  ctl.parents('form').find('.authmode').hide();
  $('#' + mode).show();
  return ctl;
}
