$(document).ready(function () {
  // Красивое добавление.
  $('#todosubmitform').submit(function () {
    $('#todosubmitform .submit').attr('disabled', 'disabled');

    $.ajax({
      type: 'POST',
      url: $(this).attr('action'),
      data: $(this).serialize(),
      dataType: 'json',

      success: function (data) {
        if (data.status == 'created') {
          $('div.todolist.open').prepend(data.html);
          $('#item-'+ data.id).hide().show('slow');
          $('#item-'+ data.id).children('.checkbox').change(todo_on_change);

          $('h3.todolist.open.empty').removeClass('empty');
          $('p.todolist.open.emptymsg').css('display', 'none');
        } else {
          alert('Oops: '+ data.message);
        }
      },

      error: function () {
        alert('Oops.');
      },

      complete: function () {
        $('#todotext').attr('value', '');
        todo_display_magic();
      }
    });

    return false;
  });

  // Быстрое управление.
  $('.todolist .checkbox').change(todo_on_change);

  // Быстрое удаление.
  $('.todolist .delete').click(function () {
    var nid = $(this).parent().children('.checkbox').attr('value');

    $.ajax({
      type: 'GET',
      url: 'nodeapi.rpc?action=delete&node='+ nid,
      dataType: 'json',

      success: function (data) {
        if (data.status == 'ok')
          $('#todo-item-'+ nid).remove();
      }
    });
  });

  /*
  // Добавление не себе.
  $('.todo-setuser u').click(function () {
    $(this).hide();
    $('.todo-setuser .hidden').removeClass('hidden');
  });
  */

  $('#todotext').focus();
});

function todo_display_magic()
{
  $('p.todolist.open').css('display', $('div.todolist.open input').length ? 'none' : '');
  $('p.todolist.closed').css('display', $('div.todolist.closed input').length ? 'none' : '');
  $('h3.todolist.closed').css('display', $('div.todolist.closed input').length ? '' : 'none');
  $('#todosubmitform .submit').attr('disabled', '');
}

function todo_on_change()
{
  var taskid = $(this).attr('value');
  var divid = $(this).parent().attr('id');
  var task = $(this).parent();

  if (!taskid) {
    alert('Oops?');
    return;
  }

  if ($(this).attr('checked')) {
    var comment = prompt('Прокомментируйте причину закрытия или нажмите «отмену», если передумали закрывать.', 'Сделано.');

    if (undefined == comment) {
      $(this).attr('checked', '');
      return;
    }
  }

  $(this).attr('disabled', 'disabled');

  $.ajax({
    type: 'POST',
    url: 'todo.rpc?action=toggle&id='+ taskid,
    data: { comment: comment },
    dataType: 'json',

    success: function (data) {
      if (data.status != 'ok') {
        alert('Oops: '+ data.message ? data.message : 'unknown error.');
      } else {
        var sel = 'div.todolist.'+ data.state;

        if ($(sel).length > 0) {
          $('#'+ divid).prependTo(sel);
        }

        $('h3.todolist.empty').removeClass('empty');
      }
    },

    error: function (request, errtype, e) {
      alert('Failed to toggle task '+ errtype +': '+ request);
    },

    complete: function() {
      $('.todolist .checkbox').attr('disabled', '');
      todo_display_magic();
   }
  });
}
