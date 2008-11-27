$(document).ready(function () {
  $('u').click(function () {
    $('div.cdata').removeClass('hidden').html($('span', $(this).parent()).html());
  });
});
