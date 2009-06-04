// перечень доступных команд
mcms.console.commands = {
	help: {
		params: [],
		description: 'Выводит список доступных команд.',
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
		description: 'Открывает страницу с формой редактирования указанного объекта.',
		exec: function(params, modifiers) {
			mcms.console.display('переход к редактированию объекта с id' + params[0]);
			//mcms.console.display('<a href="./admin/edit/' + params[0] + '" target="blank">переход к редактированию объекта с id' + params[0] + '</a>', true);
			location.href = './admin/edit/' + params[0] + '?destination=' + escape(location.href);
		}
	},
	dump: {
		params: ['id объекта'],
		description: 'Открывает страницу с информацией об объекте в виде XML.',
		exec: function(params, modifiers) {
			mcms.console.display('отображение содержимого объекта с id' + params[0]);
			location.href = './nodeapi/dump?node=' + params[0];
		}
	},
	xml: {
		params: ['id объекта'],
		description: 'Загружает в консоль информацию об объекте в виде XML.',
		exec: function(params, modifiers) {
			$.ajax({
				url: './nodeapi/dump',
				cache: false,
				data: 'node=' + params[0],
				error: function(request, status, error) {
					mcms.console.display(request.responseText, false, 'error');
				},
				success: function(response) {
					/*
					var r = $.xmlToJSON(response);
					var result = '<h2>' + r.RootName + '</h2><ul>';
					$.each(mcms.console.commands, function(k, v) {
						result += '<li><strong>' + k + '</strong>: ' + v.description + '</li>'; 
					});
					result += '</ul>';
					mcms.console.display(result, true);
					*/
					mcms.console.display( (new XMLSerializer() ).serializeToString(response) );
				}
			});
		}
	},
	add: {
		params: ['тип объекта'],
		description: 'Открывает страницу с формой для добавления объекта указанного типа.',
		exec: function(params, modifiers) {
			mcms.console.display('переход к созданию документа типа ' + params[0]);
			location.href = './admin/create/' + params[0] + '?destination=' + escape(location.href);
		}
	},
	update: {
		params: ['id объекта'],
		description: 'Принудительно сбрасывает закэшированную информацию об объекте.',
		exec: function(params, modifiers) {
			mcms.console.display('обновление xml объекта с id ' + params[0] + ' ...');
			$.get('./nodeapi/refresh?node=' + params[0] + '&destination=' + escape('./admin'), function(response) {
				mcms.console.display('готово');
			});
		}
	}
};