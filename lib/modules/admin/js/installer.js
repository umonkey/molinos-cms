$(document).ready(function () {
  $('#dbtype').change(set_db_type);
  set_db_type();
});

function set_db_type()
{
  var type = $('#dbtype').val();

  if (type == 'sqlite') {
    $('#db-server').hide();
    $('#db-user').hide();
    $('#db-password').hide();
  } else {
    $('#db-server').show();
    $('#db-user').show();
    $('#db-password').show();
  }
}
