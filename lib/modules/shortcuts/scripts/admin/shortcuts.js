/*
 * Шорткаты
 */

// не для ие
if (!!+'\v1') {
	$(function() {
		mcms.shortcuts = {
			list: {},
			query: '',
			initiated: false,
			visible: false,
			conversions: {
				'q': 'й',
				'w': 'ц',
				'e': 'у',
				'r': 'к',
				't': 'е',
				'y': 'н',
				'u': 'г',
				'i': 'ш',
				'o': 'щ',
				'p': 'з',
				'[': 'х',
				']': 'ъ',
				
				'a': 'ф',
				's': 'ы',
				'd': 'в',
				'f': 'а',
				'g': 'п',
				'h': 'р',
				'j': 'о',
				'k': 'л',
				'l': 'д',
				';': 'ж',
				'\'': 'э',
				
				'z': 'я',
				'x': 'ч',
				'c': 'с',
				'v': 'м',
				'b': 'и',
				'n': 'т',
				'm': 'ь',
				',': 'б',
				'.': 'ю'
			}
		};
		
		mcms.shortcuts.init = function() {
			$('<div id="shortcuts"><div class="container"><span class="pressed"></span><ol class="matches"></ol></div></div>').insertAfter($('#toolbar') );
			mcms.shortcuts.initiated = true;
		};
		
		mcms.shortcuts.show = function() {
			$('#shortcuts').show();
			mcms.shortcuts.visible = true;
		};
		
		// убирает окно, очищает текст запроса и список подходящих эл-ов
		mcms.shortcuts.hide = function() {
			$('#shortcuts').hide();
			mcms.shortcuts.query = '';
			mcms.shortcuts.visible = false;
		};
		
		mcms.shortcuts.getMatches = function(query) {
			var matches = [];
			for (key in mcms.shortcuts.list) {
				if ($.trim(key).indexOf(query) === 0) {
					matches.push(key);
				}
			}
			return matches;
		};
		
		mcms.shortcuts.displayMatches = function(matches) {
			if (!mcms.shortcuts.visible) {
				mcms.shortcuts.show();
			}
			if (matches.length === 0) {
				$('#shortcuts').removeAttr('class').addClass('none');
			} else if (matches.length == 1) {
				$('#shortcuts').removeAttr('class').addClass('one');
			} else {
				$('#shortcuts').removeAttr('class').addClass('multiple');
			}
			$('#shortcuts .matches').empty();
			$.each(matches, function() {
				$('#shortcuts .matches').append('<li>' + this + '</li>');
			});
		};
		
		mcms.shortcuts.getKey = function(which) {
			var key = String.fromCharCode(which);
			if (mcms.shortcuts.conversions[key]) {
				return mcms.shortcuts.conversions[key];
			} else if (key.match(/[а-я]/) ) {
				return key;
			} else if (which == 13 && mcms.shortcuts.visible || which == 8 && mcms.shortcuts.visible) {
				return true;
			}
		};
		
		// собираем список всех эл-ов навигации
		$('#toolbar ul.navigation li a').each(function() {
			mcms.shortcuts.list[$(this).text().toLowerCase()] = $(this).attr('href');
		});
		
		// keypress не ловит нажатие esc
		$(document).keyup(function(e) {
			if (e.which == 27) {
				mcms.shortcuts.hide();
			}
		});
		
		$(document).keypress(function(e) {
			
			var key = mcms.shortcuts.getKey(e.which);
			// не реагируем когда курсор в поле или области
			if (!key || $(e.target).is('input') || $(e.target).is('textarea') || e.ctrlKey || e.altKey) {
				return true;
			}
			
			if (!mcms.shortcuts.initiated) {
				mcms.shortcuts.init();
			}
			
			if (e.keyCode == 8 && mcms.shortcuts.visible) {
				mcms.shortcuts.query = mcms.shortcuts.query.slice(0, -1);
				if (mcms.shortcuts.query === '') {
					mcms.shortcuts.hide();
					return false;
				}
			} else if (e.keyCode != 13) {
				mcms.shortcuts.query += key;
			}
			
			$('#shortcuts .pressed').empty().text(mcms.shortcuts.query);		
			var matches = mcms.shortcuts.getMatches(mcms.shortcuts.query);
			
			if (e.keyCode == 13 && matches.length == 1) {
				location.href = mcms.shortcuts.list[matches[0] ];
				return false;
			} else if (e.keyCode == 13) {
				return false;
			} else {
				mcms.shortcuts.displayMatches(matches);
			}
			
			if (e.keyCode == 8 && mcms.shortcuts.visible) {
				return false;
			}
		});
	});
}