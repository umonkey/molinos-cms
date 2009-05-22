// Массовые операции над объектами.
$(function () {
  // Выделение.
  $('.selink').click(function () {
    var c = $(this).attr('class').replace(/.* select-/, '');
    switch (c) {
    case 'all':
      $('.nodes :checkbox').attr('checked', 'checked');
      break;
    case 'none':
      $('.nodes :checkbox').attr('checked', '');
      break;
    default:
      $('.nodes :checkbox').removeAttr('checked');
      $('.nodes li.' + c + '>span.actions :checkbox').attr('checked', 'checked');
      $('.nodes tr.' + c + ' :checkbox').attr('checked', 'checked');
    }
  });

  // Действия.
  $('.actionlink').click(function () {
    if ($('table.nodes :checked').size()) {
      var action = $(this).attr('class').replace(/.*action-/, '');
      var form = $(this).parents('form:eq(0)');

      switch (action) {
      case 'edit':
        action = 'admin/files/edit?redir=1&';
        break;
      default:
        action = 'nodeapi/' + action + '?';
        break;
      }

      action += 'destination=' + escape(window.location.href);

      form.attr('action', action);
      form.submit();
    }
  });
});
