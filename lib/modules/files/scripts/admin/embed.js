$(function () {
  var code = $('#embedsrc').html();
  if (code) {
    $('#embedtgt').html('<div class="code">' + code.split('&').join('&amp;').split('<').join('&lt;').split('>').join('&gt;') + '</div>');
    $('#embedtgt').parents('tr:eq(0)').show();
  }
});
