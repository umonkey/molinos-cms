// При клике в заголовок поля разворачиваем его свойства, сворачиваем все остальные.
$(document).ready(function () {
  // Раскрытие свойств поля при клике в заголовок.
  $('div.fprop span.caption').click(function () {
    // При клике в новое поле дублируем его.
    if ($(this).hasClass('addnew')) {
      var ctl = $(this).parents('div.fprop:eq(0)');
      var nnn = ctl.clone(true);

      // Модифицируем ключ к массиву.
      nnn.find('.clonable').each(function () {
        $(this).attr('name', $(this).attr('name').replace('node_content_fields[', 'node_content_fields[1'));
      });

      ctl.parent().append(nnn);

      // Дальнейшие клики в этот элемент не обрабатываем.
      $(this).removeClass('addnew');
    }

    $('table.fprops', $(this).parent().parent()).css('display', 'none');
    $('table.fprops', $(this).parent()).css('display', 'block');
  });

  // Правим класс обёртки при изменении типа, чтобы скрыть/показать нужные свойства.
  $('tr.fprop.type select').change(function () {
    $(this).parents('div.fprop:eq(0)')
      .attr('class', 'fprop '+ $(this).val());
  });

  // Правим подсвеченное имя поля при изменении желаемого.
  $('tr.fprop.name input').change(function () {
    $('span.caption', $(this).parents('div.fprop:eq(0)'))
      .html($(this).val());
  });

  // Перечёркивание при удалении.
  $('tr.fprop.delete input').change(function () {
    $('span.caption', $(this).parents('div.fprop:eq(0)')) .css('text-decoration', $(this).attr('checked') ? 'line-through' : 'underline');
  });
});
