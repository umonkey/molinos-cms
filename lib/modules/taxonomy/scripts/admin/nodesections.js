$(function () {
  $('#nodesections .toggle').change(function () {
    $(this).parents('table:eq(0)').find('tbody input[@type="checkbox"]').attr('checked', $(this).attr('checked'));
  });
});
