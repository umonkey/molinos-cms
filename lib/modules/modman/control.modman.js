$(document).ready(function () {
  $('.modman-filter select').change(function () {
    var filter = $(this).attr('value');
    if (filter == '') {
      $('.modman-wrapper tr').show();
    } else {
      $('.modman-wrapper tr').hide();
      $('.modman-wrapper tr.' + filter).show();
    }
  });
});
