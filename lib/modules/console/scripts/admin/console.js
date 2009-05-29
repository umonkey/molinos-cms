$(function() {
	// консоль
	mcms.console = {
		jObj: $('<div id="console"><div class="container"><h1>Console 29.5.9-1</h1><div class="display"><ul /></div><div class="input"><form action=""><input type="text" /></form></div><div class="note">Для вывода доступных команд введите "help"</div></div></div>'),
		initiated: false,
		visible: false,
		history: [],
		currentHistoryEntry: 0,
		mode: 'compact',
		init: function() {
			mcms.console.jObj.appendTo('body').dblclick(function() {
				if (mcms.console.mode == 'compact') {
					$('html, body').css({
						overflow: 'hidden'
					});
					$(this).css({
						position: 'absolute',
						height: '100%',
						top: 0
					});
					$(this).find('.display').css({
						height: $(this).height() - $(this).find('h1').height() - $(this).find('input').height() - $(this).find('.note').height() - 65
					});
					mcms.console.mode = 'expanded';
				} else {
					$('html, body').css({
						overflow: 'visible'
					});
					$(this).css({
						position: 'fixed',
						height: 'auto',
						top: '2.667em'
					});
					$(this).find('.display').css({
						height: '13.35em'
					});
					mcms.console.mode = 'compact';
				}
			});
			mcms.console.initiated = true;
			$('#console form').submit(function() {
				var input = $('#console input').val(), interpretedInput = interpretInput(input);
				//mcms.console.history.push(input);
				//mcms.console.currentHistoryEntry++;
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
				$('#console input').val('');
				return false;
			});
		},
		hide: function() {
			mcms.console.jObj.hide();
			mcms.console.visible = false;
		},
		show: function() {
			mcms.console.jObj.show();
			mcms.console.visible = true;
			setTimeout(function() {
				$('#console input')[0].focus();
			}, 100);
		},
		display: function(message, ishtml, type) {
			if (message) {
				var element = $('<li />');
				if (type) {
					element.addClass(type); 
				} 
				if (ishtml) {
					element.html(message).appendTo($('#console .display>ul') );
				} else {
					element.text(message).appendTo($('#console .display>ul') );
				}
				$('#console .display')[0].scrollTop = $('#console .display')[0].scrollHeight;
			}
		}
	};
	
	// перечень доступных команд
	mcms.console.commands = {
		help: {
			description: 'Выводит список доступных команд. Параметры: нет. Модификаторы: нет.',
			exec: function(params, modifiers) {
				var result = '<ul>';
				$.each(mcms.console.commands, function(k, v) {
					result += '<li><strong>' + k + '</strong>: ' + v.description + '</li>'; 
				});
				result += '</ul>';
				mcms.console.display(result, true);
			}
		},
		edit: {
			params: ['id объекта'],
			description: 'edit',
			exec: function(params, modifiers) {
				mcms.console.display('переход к редактированию объекта с id' + params[0]);
				location.href = './admin/edit/' + params[0] + '?destination=' + escape(location.href);
			}
		},
		dump: {
			params: ['id объекта'],
			description: 'dump',
			exec: function(params, modifiers) {
				mcms.console.display('отображение содержимого объекта с id' + params[0]);
				location.href = './nodeapi/dump?node=' + params[0];
			}
		},
		xml: {
			params: ['id объекта'],
			description: 'xml',
			exec: function(params, modifiers) {
				$.ajax({
					url: './nodeapi/dump',
					cache: false,
					data: 'node=' + params[0],
					error: function(request, status, error) {
						mcms.console.display(request.responseText, false, 'error');
					},
					success: function(response) {
						mcms.console.display( (new XMLSerializer() ).serializeToString(response) );
					}
				});
			}
		},
		add: {
			params: ['тип объекта'],
			description: 'create',
			exec: function(params, modifiers) {
				mcms.console.display('переход к созданию документа типа ' + params[0]);
				location.href = './admin/create/' + params[0] + '?destination=' + escape(location.href);
			}
		},
		update: {
			params: ['id объекта'],
			description: 'update',
			exec: function(params, modifiers) {
				mcms.console.display('обновление xml объекта с id ' + params[0] + ' ...');
				$.get('./nodeapi/refresh?node=' + params[0] + '&destination=' + escape('./admin'), function(response) {
					mcms.console.display('готово');
				});
			}
		}
	};
	
	function interpretInput(input) {
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
	
	$(document).keyup(function(e) {
		if (e.which == 27 && mcms.console.visible && !mcms.shortcuts || e.which == 27 && mcms.console.visible && mcms.shortcuts && !mcms.shortcuts.visible) {
			mcms.console.hide();
			return false;
		} else if (!$(e.target).is('input') && !$(e.target).is('textarea') && (e.keyCode == 192 || e.keyCode == 1025) ) {
			if (!mcms.console.initiated) {
				mcms.console.init();
			}
			mcms.console.show();
			return false;
		}
	});
});
