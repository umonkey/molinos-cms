$(function() {
	
	// календарь
	$('input.mode-datetime, input.mode-date').datepicker({
		dateFormat: 'yy-mm-dd',
		constrainInput: false,
		duration: 0,
		beforeShow: function() {
			var splittedVal = $(this).val().split(' ');
			if (splittedVal[1]) {
				$(this).attr('time', splittedVal[1]);
			}	
		},
		onSelect: function(dateText) {
			var val = dateText;
			if ($(this).attr('time') ) {
				val += ' ' + $(this).attr('time');
			}
			$(this).val(val);
		}
	});
	
});
