var mcms = {};
mcms.console = {};

$(function() {
	
	function generate() {
		var g = $('ul.panels li.panel.xmlapi-constructor .params form').attr('action');
		if ($('ul.panels li.panel.xmlapi-constructor .params form fieldset input:eq(0)').val() ) {
			g+= '?' + $('ul.panels li.panel.xmlapi-constructor .params form fieldset input:eq(0)').attr('name') + '=' + $('ul.panels li.panel.xmlapi-constructor .params form fieldset input:eq(0)').val()
		}
		$('ul.panels li.panel.xmlapi-constructor .params form fieldset input:gt(0)').each(function() {
			if ($(this).val() ) {
				g += '&' + $(this).attr('name') + '=' + $(this).val();
			}
		});
		$('ul.panels li.panel.xmlapi-constructor .result textarea:eq(0)')[0].value = g;
		
		var g1 = '<xsl:variable name="xa" select="document(concat($api, \'' + $('ul.panels li.panel.xmlapi-constructor .params form').attr('action');
		
		if ($('ul.panels li.panel.xmlapi-constructor .params form fieldset input:eq(0)').val() ) {
			g1+= '?' + $('ul.panels li.panel.xmlapi-constructor .params form fieldset input:eq(0)').attr('name') + '=' + $('ul.panels li.panel.xmlapi-constructor .params form fieldset input:eq(0)').val()
		}
		
		$('ul.panels li.panel.xmlapi-constructor .params form fieldset input:gt(0)').each(function() {
			if ($(this).val() ) {
				g1 += '&amp;' + $(this).attr('name') + '=' + $(this).val();
			}
		});
		g1 += '\') )" />';
		$('ul.panels li.panel.xmlapi-constructor .result textarea:eq(1)')[0].value = g1;
	}
	
	$.each(gconfig, function(k, v) {
		$('ul.panels li.panel.xmlapi-constructor .points>ul').append('<li class="config-group"><h3><a href="#"><span class="state">+ </span><span class="title">' + k + '</span></a></h3><ul /></li>');
		$.each(v, function(k1, v1) {
			$('ul.panels li.panel.xmlapi-constructor .points>ul>li:last>ul').append('<li><h3><a href="' + v1.documentation + '">' + v1.path + '</a></h3><p class="description">' + v1.description + '</p></li>');
		});
	});
	
	$('ul.panels li.panel.xmlapi-constructor .points>ul>li>ul>li').live('click', function(e) {
		var parent = $(this).parents('li:eq(0)');
		var group = parent.find('>h3 a span.title').text();
		$('ul.panels li.panel.xmlapi-constructor .result textarea').text('');
		var idx = parent.find('li').index(this);
		var params = gconfig[group][idx].params;
		$('ul.panels li.panel.xmlapi-constructor .params form fieldset').empty();
		$('ul.panels li.panel.xmlapi-constructor .params form').attr('action', gconfig[group][idx].path);
		
		if (params) {
			// добавляем контролы
			$.each(params, function(k, v) {
				$('ul.panels li.panel.xmlapi-constructor .params form fieldset').append('<div><p class="title">' + k + '</p><input type="text" name="' + k + '"/><p class="description">' + v + '</p></div>');
			});
			$('ul.panels li.panel.xmlapi-constructor .params form').show();
			$('ul.panels li.panel.xmlapi-constructor .params p.noparams').hide();
			generate();
		} else {
			$('ul.panels li.panel.xmlapi-constructor .params form').hide();
			$('ul.panels li.panel.xmlapi-constructor .params p.noparams').show();
			
			$('ul.panels li.panel.xmlapi-constructor .result textarea:eq(0)').text($('ul.panels li.panel.xmlapi-constructor .params form').attr('action') );
			$('ul.panels li.panel.xmlapi-constructor .result textarea:eq(1)').text('<xsl:variable name="xa" select="document(concat($api, \'' + $('ul.panels li.panel.xmlapi-constructor .params form').attr('action') + '\') )" />');
		}		
	});
	
	$('ul.panels li.panel.xmlapi-constructor .params form input').live('keyup', function() {
		generate();
	});
	
	$('ul.panels li.panel.xmlapi-constructor .params form').submit(function() {
		return false;
	});
	
	$('ul.panels li.panel.xmlapi-constructor .points>ul>li>h3>a').live('click', function() {
		if ($(this).find('span.state').text() == '+ ') {
			$(this).find('span.state').text('- ');
		} else {
			$(this).find('span.state').text('+ ');
		}
		$(this).parents('li:eq(0)').find('ul').toggle();
		return false;
	});
	
	
	
	
	
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