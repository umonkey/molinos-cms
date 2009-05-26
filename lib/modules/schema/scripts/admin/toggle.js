$(function () {
  $('table#access tbody th').click(function () {
    crud_recheck($(this).parents('tr:eq(0)').find('input'));
  });
  $('table#access thead th.c').click(function () {
    crud_recheck($(this).parents('table:eq(0)').find('input[@name$="[' + $(this).text().toLowerCase() + ']"]'));
  });
});

function crud_recheck($inputs)
{
  if ($inputs.filter(':checked').length != $inputs.length)
    $inputs.attr('checked', 'checked');
  else
    $inputs.removeAttr('checked');
  $inputs.parent().addClass('hl');
}
