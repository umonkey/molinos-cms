var mcms = {};
mcms.console = {};

$(function() {
	$.extend(mcms.console, {
		history: [],
		currentHistoryEntry: 0,
		display: function(message, ishtml, type) {
			if (message) {
				var element = $('<li />');
				if (type) {
					element.addClass(type); 
				} 
				if (ishtml) {
					element.html(message).appendTo($('#console ul.panels li.panel.console .display>ul') );
				} else {
					element.text(message).appendTo($('#console ul.panels li.panel.console .display>ul') );
				}
				$('#console ul.panels li.panel.console .display')[0].scrollTop = $('#console ul.panels li.panel.console .display')[0].scrollHeight;
			}
		},
		interpretInput: function(input) {
			var inputAsArray = input.split(' '), result = {
				command: inputAsArray[0],
				params: [],
				modifiers: {}
			};
			$.each(inputAsArray.slice(1), function(k, v) {
				if (v.indexOf('\\') != -1) {
					result.modifiers[v.replace('\\', '')] = true;
				} else {
					result.params.push(v);
				}
			});
			return result;
		}
	});
	
	$('#console li.panel').css({
		height: $('#console').height() - $('#console').find('h1').height() - $('#console').find('input.console-command').height() - $('#console').find('.note').height() - 100
	});
	
	$('ul.panel-selector li').click(function() {
		var idx = $('ul.panel-selector li').index(this);
		$('ul.panels li.panel').hide();
		$('ul.panels li.panel:eq(' + idx + ')').show();
		$(this).addClass('active').siblings().removeClass('active');
		return false;
	});
	
	$('#console ul.panels li.panel.console form').submit(function() {
		var input = $('#console ul.panels li.panel.console input.console-command').val(), interpretedInput = mcms.console.interpretInput(input);
		mcms.console.history.push(input);
		mcms.console.currentHistoryEntry = mcms.console.history.length;
		mcms.console.display('>>> ' + input);
		if (typeof mcms.console.commands[interpretedInput.command] == 'undefined') {
			mcms.console.display('неизвестная команда');
		} else if (interpretedInput.modifiers['h']) {
			mcms.console.display(mcms.console.commands[interpretedInput.command].description);
		} else {
			var pass = true;
			$.each(mcms.console.commands[interpretedInput.command].params, function(k, v) {
				if (!interpretedInput.params[k]) {
					mcms.console.display('Не указан обязательный параметр - ' + v, false, 'error');
					pass = false;
				}
			});
			if (pass) {
				mcms.console.commands[interpretedInput.command].exec(interpretedInput.params, interpretedInput.modifiers);
			}
		}
		$('#console ul.panels li.panel.console input.console-command').val('');
		return false;
	});
	
	$('#console span.clear').click(function() {
		$('#console ul.panels li.panel.console .display>ul').empty();
		return false;
	});
	
	$(document).keyup(function(e) {
		if (e.which == 38 && $(e.target).is('input.console-command') ) {
			if (mcms.console.history[mcms.console.currentHistoryEntry - 1]) {
				$('#console ul.panels li.panel.console input.console-command').val(mcms.console.history[--mcms.console.currentHistoryEntry]);
			} else {
				$('#console ul.panels li.panel.console input.console-command').val('');
				mcms.console.currentHistoryEntry = -1;
			}
		}
		if (e.which == 40 && $(e.target).is('input.console-command') ) {
			if (mcms.console.history[mcms.console.currentHistoryEntry + 1]) {
				$('#console ul.panels li.panel.console input.console-command').val(mcms.console.history[++mcms.console.currentHistoryEntry]);
			} else {
				$('#console ul.panels li.panel.console input.console-command').val('');
				mcms.console.currentHistoryEntry = mcms.console.history.length;
			}
		}
	});
});