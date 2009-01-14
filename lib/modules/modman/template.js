$(document).ready(function () {
  $('.modman .filter select').change(function () {
    var filter = $(this).attr('value');
    if (filter == '') {
      $('.modman tr').show();
    } else {
      $('.modman tr').hide();
      $('.modman tr.' + filter).show();
    }
  });
});
